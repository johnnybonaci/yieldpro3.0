<?php

namespace App\Services\Leads;

use App\ValueObjects\Period;
use App\Models\Leads\Provider;
use Illuminate\Support\Collection;
use App\Traits\Leads\AddParameters;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Model;
use App\Interfaces\Leads\ImportInterface;
use Illuminate\Contracts\Config\Repository as Config;

class ImportService implements ImportInterface
{
    use AddParameters;

    public $do = 1;

    public $per_page = 250;

    public $page = 1;

    protected $provider;

    protected $auth_token;

    protected $columns;

    protected $table;

    private $repository;

    /**
     * Summary of __construct.
     */
    public function __construct(
        private Config $config,
    ) {
    }

    /**
     * Summary of import.
     */
    public function import(Model $models, int $provider, Period $period): int
    {
        $this->provider = Provider::find($provider);
        $this->auth_token = __toHashValidated(__toEnviroment($this->provider->service, 'AUTH_TOKEN', $provider), $this->provider->api_key);

        [$this->table, $this->columns, $this->page, $fields, $repository, $collects] = self::list($models);

        $this->repository = new $repository();
        $accumulative = new Collection();

        $query_parameters = self::make($this->columns, $this->per_page, $this->page);
        if ($this->table === 'offers') {
            $query_parameters = array_merge($query_parameters, ['serializer' => 'offer_grid']);
        }

        do {
            $response = Http::withHeaders(['Authorization' => 'Basic ' . $this->auth_token])
                        ->get($this->provider->url . '/' . $this->table, $query_parameters)
                        ->collect($collects)
                        ->filter(fn ($filters) => is_numeric($filters[$fields]))
                        ->mapWithKeys(fn ($data) => $this->repository->resource($data, $provider));

            $accumulative = $accumulative->merge($response);
            $this->do = $response->count();
            $query_parameters['page'] = ++$this->page;
        } while ($this->do);

        return $this->repository->create($accumulative->filter(), $this->per_page);
    }
}
