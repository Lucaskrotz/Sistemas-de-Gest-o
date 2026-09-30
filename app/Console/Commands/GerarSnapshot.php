<?php

namespace App\Console\Commands;

use App\Models\Chamado;
use App\Models\Cliente;
use App\Models\Contato;
use App\Models\Pagamento;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\Tenant;
use App\Models\Titulo;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

/**
 * Gera a versão estática (GitHub Pages): renderiza toda tela GET de cada módulo, logado como o
 * usuário demo, e grava {rota}/index.html. Formulários/AJAX de escrita são desativados no layout
 * quando APP_SNAPSHOT=true; os gráficos do Indicadores leem JSONs pré-gerados por período.
 */
class GerarSnapshot extends Command
{
    protected $signature = 'snapshot:gerar
        {--base= : URL pública do site estático, ex.: https://usuario.github.io/repo}
        {--saida=_site : Pasta de saída}';

    protected $description = 'Gera a versão estática (somente leitura) das demos para o GitHub Pages';

    /** parâmetro de rota => model cujos registros viram páginas */
    private const PARAMETROS = [
        'cliente' => Cliente::class, 'produto' => Produto::class, 'pedido' => Pedido::class,
        'chamado' => Chamado::class, 'titulo' => Titulo::class, 'pagamento' => Pagamento::class,
        'contato' => Contato::class,
    ];

    /** períodos rápidos do Indicadores => [dias ou null, meses ou null] (mesma regra do JS da tela) */
    private const PERIODOS = ['7' => [7, null], '30' => [30, null], '90' => [90, null], '12m' => [null, 12]];

    private string $saida;

    private string $base;

    private int $paginas = 0;

    public function handle(Kernel $kernel): int
    {
        if (! config('app.snapshot')) {
            $this->error('Defina APP_SNAPSHOT=true (o layout precisa do modo estático).');

            return self::FAILURE;
        }
        $base = rtrim((string) $this->option('base'), '/');
        if (! filter_var($base, FILTER_VALIDATE_URL)) {
            $this->error('Informe --base com a URL pública, ex.: https://usuario.github.io/repo');

            return self::FAILURE;
        }

        // Todas as URLs geradas (route/url/asset) apontam para o endereço público do site estático.
        URL::forceRootUrl($base);
        URL::forceScheme(parse_url($base, PHP_URL_SCHEME));
        $this->base = $base;

        $this->saida = base_path($this->option('saida'));
        File::deleteDirectory($this->saida);
        File::ensureDirectoryExists($this->saida);
        File::copyDirectory(public_path('css'), "{$this->saida}/css");
        File::copy(public_path('favicon.ico'), "{$this->saida}/favicon.ico");
        File::put("{$this->saida}/.nojekyll", ''); // GitHub Pages: servir arquivos como estão

        $this->pagina($kernel, '/');

        foreach (config('modulos') as $chave => $modulo) {
            $usuario = User::where('email', "{$chave}@demo.test")->firstOrFail();
            auth()->setUser($usuario);
            Tenant::definir($usuario->tenant); // só Tenancy usa; nos demais é null
            $this->redirecionamento("/demo/{$chave}", url($modulo['rota']));

            foreach (Route::getRoutes() as $rota) {
                $nome = (string) $rota->getName();
                if (! str_starts_with($nome, "{$chave}.") || ! in_array('GET', $rota->methods()) || $nome === 'indicadores.dados') {
                    continue;
                }
                foreach ($this->urls($nome, $rota->parameterNames()) as $caminho) {
                    $this->pagina($kernel, $caminho);
                }
            }
        }

        $this->dadosIndicadores($kernel);
        $this->info("Snapshot gerado: {$this->paginas} arquivos em {$this->option('saida')}/ (base {$base})");

        return self::SUCCESS;
    }

    /** Caminhos relativos ("/erp/clientes/3/edit") de uma rota, um por registro quando tem parâmetro. */
    private function urls(string $nome, array $parametros): array
    {
        if (! $parametros) {
            return [$this->relativo(route($nome))];
        }
        $model = self::PARAMETROS[$parametros[0]] ?? null;
        if (count($parametros) > 1 || ! $model) {
            $this->warn("Ignorada (parâmetro sem mapeamento): {$nome}");

            return [];
        }

        return $model::query()->pluck('id')->map(fn ($id) => $this->relativo(route($nome, $id)))->all();
    }

    /** "https://usuario.github.io/repo/erp/clientes" → "/erp/clientes" (caminho que o app entende). */
    private function relativo(string $absoluta): string
    {
        return '/'.ltrim(substr($absoluta, strlen($this->base)), '/');
    }

    private function pagina(Kernel $kernel, string $caminho): void
    {
        $resposta = $kernel->handle(Request::create($caminho, 'GET'));
        if ($resposta->getStatusCode() !== 200) {
            $this->warn("{$caminho} → HTTP {$resposta->getStatusCode()} (ignorada)");

            return;
        }
        $this->gravar(rtrim($caminho, '/').'/index.html', $resposta->getContent());
    }

    private function redirecionamento(string $caminho, string $destino): void
    {
        $destino = e($destino);
        $this->gravar("{$caminho}/index.html", "<!doctype html><meta charset=\"utf-8\"><meta http-equiv=\"refresh\" content=\"0; url={$destino}\"><link rel=\"canonical\" href=\"{$destino}\"><a href=\"{$destino}\">Abrir demo</a>");
    }

    private function dadosIndicadores(Kernel $kernel): void
    {
        auth()->setUser(User::where('email', 'indicadores@demo.test')->firstOrFail());
        foreach (self::PERIODOS as $arquivo => [$dias, $meses]) {
            $inicio = $meses ? today()->subMonthsNoOverflow($meses - 1)->startOfMonth() : today()->subDays($dias - 1);
            $requisicao = Request::create($this->relativo(route('indicadores.dados')), 'GET',
                ['inicio' => $inicio->toDateString(), 'fim' => today()->toDateString()]);
            $requisicao->headers->set('Accept', 'application/json');
            $resposta = $kernel->handle($requisicao);
            if ($resposta->getStatusCode() !== 200) {
                throw new \RuntimeException("indicadores/dados ({$arquivo}) → HTTP {$resposta->getStatusCode()}");
            }
            $this->gravar("/indicadores/dados/{$arquivo}.json", $resposta->getContent());
        }
    }

    private function gravar(string $caminho, string $conteudo): void
    {
        $arquivo = $this->saida.$caminho;
        File::ensureDirectoryExists(dirname($arquivo));
        File::put($arquivo, $conteudo);
        $this->paginas++;
    }
}
