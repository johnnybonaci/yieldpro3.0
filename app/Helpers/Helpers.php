<?php

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Repositories\LogRepository;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Services\Leads\ValidatedService;
use App\Repositories\Leads\PubRepository;
use App\Repositories\Leads\LeadMetricRepository;

/**
 * Summary of __toService.
 */
function __toService(string $string): string
{
    $string = Str::of($string)->explode('\\')->last();

    return Str::of($string)->snake()->__toString();
}

/**
 * Summary of __toConclusion from transcript.
 */
function __toConclusion(?string $json): ?string
{
    $str = json_decode($json);
    $str = $str->detailed ?? null;
    if (!$str) {
        return null;
    }
    $str = Str::markdown($str, ['html_input' => 'strip']);
    $str = Str::of($str)->explode('>Conclusion<');
    $str = Str::of($str->last())->stripTags();
    $str = Str::of($str->value())->remove(['/h1>', '/h2>', '/h3>', '/h4>', '/h5>', '/h6>']);

    return trim($str);
}

function __toAnalisys(?string $json, string $key): ?string
{
    $str = json_decode($json);
    $str = $str->$key ?? null;
    if (!$str) {
        return null;
    }
    $str = Str::markdown($str, ['html_input' => 'strip']);
    $str = Str::of($str)->stripTags();
    $str = Str::of($str->value())->remove(['/h1>', '/h2>', '/h3>', '/h4>', '/h5>', '/h6>']);

    return trim($str);
}

/**
 * Summary of __toMinutes.
 */
function __toMinutes(?int $seconds): ?string
{
    if ($seconds === null || $seconds === 0) {
        return null;
    }

    $minutes = str_pad((string) floor($seconds / 60), 2, '0', STR_PAD_LEFT);
    $remainingSeconds = str_pad((string) ($seconds % 60), 2, '0', STR_PAD_LEFT);

    return "{$minutes}:{$remainingSeconds}";
}

/**
 * Summary of __toContains.
 */
function __toContains(string $str, string $compare): bool
{
    $str = Str::upper($str);

    return Str::contains($str, $compare);
}

function __toMakePath(string $dir, string $folder): string
{
    $make = true;
    $path = storage_path($dir . '/' . $folder);
    $directory = Storage::directories($dir);

    foreach ($directory as $row) {
        $make = $row == 'app/recordings' ? false : true;
    }
    $make === true ? File::makeDirectory($path, 0777, true, true) : false;

    return $path;
}
/**
 * Summary of __toJob.
 * @param mixed $room
 */
function __toJob(Model $model, $room = false): array
{
    $string = $model->service;
    $string = Str::of(__toService($string))->replace('service', 'job')->__toString();
    $list_job = Str::of($string)->replace('_job', '')->__toString();
    $job = ucfirst(Str::camel($string));
    $response['queue'] = $list_job;
    if ($room) {
        $response['queue'] = '2_' . $list_job;
    }
    if ($list_job != 'track_drive' && $list_job != 'convoso_call') {
        $job = 'PhoneRoomJob';
    }
    $response['job'] = 'App\\Jobs\\Leads\\' . $job;
    $response['model'] = $model;

    return $response;
}
/**
 * Summary of __toClass.
 */
function __toClass(string $string): mixed
{
    return new $string(new LogRepository(new ValidatedService(new PubRepository(), new LeadMetricRepository(), new Request())));
}

/**
 * Summary of __toHashValidated.
 */
function __toHashValidated(string $string, string $hash): string|bool
{
    if (Hash::check($string, $hash)) {
        return $string;
    }

    return false;
}

/**
 * Summary of __toHash.
 */
function __toHash(string $string): string
{
    return Hash::make($string, [
        'memory' => 65536,
        'time' => 6,
        'threads' => 4,
    ]);
}

/**
 * Summary of __toEnviroment.
 */
function __toEnviroment(string $service, string $string, int $id): string
{
    return env($id . '_' . Str::upper(__toService($service)) . '_' . $string);
}

/**
 * Summary of __toCheckSources.
 */
function __toCheckSources(array $setup, Model $model): bool
{
    $response = false;
    $string = __toSingularModel($model);
    if (isset($setup[$string][$model->id]) && $setup[$string][$model->id] && class_exists($model->service)) {
        $response = true;
        if ($string == 'phone_room' && !array_key_exists('call_center', $setup)) {
            $response = false;
        }
    }

    return $response;
}

/**
 * Summary of __toSingularModel.
 */
function __toSingularModel(Model $model): string
{
    return Str::singular($model->getTable());
}

/**
 * Summary of __toException101.
 */
function __toException101(array $data): array
{
    $fields = ['lead_token', 'caller_id', 'traffic_source_id', 'source_url', 'jornaya_leadid', 's1', 'created_time'];
    if ($data['s1'] == '101' && $data['lead_type'] == 'MC') {
        if (empty($data['jornaya_leadid'])) {
            return [];
        }
        $data = array_filter($data, function ($key) use ($fields) {
            return in_array($key, $fields);
        }, ARRAY_FILTER_USE_KEY);
    }

    return $data;
}

/**
 * Summary of __toRangePassDay.
 */
function __toRangePassDay(string $start, string $end): array
{
    $start = Carbon::parse($start);
    $end = Carbon::parse($end);
    $diff = $start->diffInDays($end) + 1;
    $diffStart = $diff;
    if ($diff == 1 && $end->dayOfWeek == 1) {
        $diffStart = 3;
    }
    $data['newstart'] = $start->subDays($diffStart)->format('Y-m-d');
    $data['newend'] = $end->subDays($diff)->format('Y-m-d');

    return $data;
}

/**
 * Summary of __toFields.
 */
function __toFields(string $string): string
{
    return str_replace('1', '_', str_replace('_', '.', $string));
}

if (!function_exists('merge')) {
    function merge($arrays)
    {
        $result = [];

        foreach ($arrays as $array) {
            if ($array !== null) {
                if (gettype($array) !== 'string') {
                    foreach ($array as $key => $value) {
                        if (is_integer($key)) {
                            $result[] = $value;
                        } elseif (isset($result[$key]) && is_array($result[$key]) && is_array($value)) {
                            $result[$key] = merge([$result[$key], $value]);
                        } else {
                            $result[$key] = $value;
                        }
                    }
                } else {
                    $result[count($result)] = $array;
                }
            }
        }

        return join(' ', $result);
    }
}

if (!function_exists('uncamelize')) {
    function uncamelize($camel, $splitter = '_')
    {
        $camel = preg_replace('/(?!^)[[:upper:]][[:lower:]]/', '$0', preg_replace('/(?!^)[[:upper:]]+/', $splitter . '$0', $camel));

        return strtolower($camel);
    }
}
