<?php
declare(strict_types=1);

namespace Tabilet;

use Genelet\Controller;
use Genelet\Logger;
use PDO;
use RuntimeException;

final class Application
{
    private const COMPONENTS = ["admin", "car"];

    public static function config(): object
    {
        $root = dirname(__DIR__);
        $config = json_decode(file_get_contents($root . "/conf/config.json"));
        $config = self::expandEnv($config);

        $config->{"Document_root"} = self::path($root, $config->{"Document_root"});
        $config->{"Template"} = self::path($root, $config->{"Template"});
        $config->{"Uploaddir"} = self::path($root, $config->{"Uploaddir"});
        $config->{"Log"}->{"Filename"} = self::path($root, $config->{"Log"}->{"Filename"});

        self::ensureDir($config->{"Uploaddir"});
        self::ensureDir(dirname($config->{"Log"}->{"Filename"}));

        $dsn = getenv("GENELET_SAMPLE_DB_DSN");
        if ($dsn !== false && $dsn !== "") {
            $config->{"Db"} = [
                $dsn,
                getenv("GENELET_SAMPLE_DB_USER") ?: "root",
                getenv("GENELET_SAMPLE_DB_PASSWORD") ?: "",
            ];
        }

        return $config;
    }

    public static function pdo(object $config): PDO
    {
        return new PDO(...$config->{"Db"});
    }

    public static function logger(object $config): Logger
    {
        return new Logger($config->{"Log"}->{"Filename"}, $config->{"Log"}->{"Level"});
    }

    public static function components(PDO $pdo): array
    {
        $jsons = [];
        $storage = [];
        foreach (self::COMPONENTS as $item) {
            $jsons[$item] = json_decode(file_get_contents(__DIR__ . "/$item/component.json"));
            $class = "\\Tabilet\\" . ucfirst($item) . "\\Model";
            $storage[$item] = new $class($pdo, $jsons[$item]);
        }

        return [$jsons, $storage];
    }

    public static function controller(object $config = null): Controller
    {
        $config = $config ?? self::config();
        $pdo = self::pdo($config);
        [$jsons, $storage] = self::components($pdo);

        return new Controller($config, $pdo, $jsons, $storage, self::logger($config));
    }

    public static function render(object $response, object $config): ?array
    {
        if ($response->is_json) {
            return null;
        }

        $paths = [$config->{"Template"} . "/" . $response->role];
        if (!empty($response->component)) {
            $paths[] = $config->{"Template"} . "/" . $response->role . "/" . $response->component;
        }

        $twig = new \Twig\Environment(new \Twig\Loader\FilesystemLoader($paths));
        return [$twig, "render"];
    }

    private static function path(string $root, string $path): string
    {
        if ($path !== "" && ($path[0] === "/" || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1)) {
            return $path;
        }

        return $root . "/" . $path;
    }

    private static function ensureDir(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
    }

    private static function expandEnv($value)
    {
        if (is_object($value)) {
            foreach (get_object_vars($value) as $key => $item) {
                $value->{$key} = self::expandEnv($item);
            }
            return $value;
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = self::expandEnv($item);
            }
            return $value;
        }

        if (is_string($value) && preg_match('/^\$\{([A-Z_][A-Z0-9_]*)\}$/', $value, $matches) === 1) {
            $env = getenv($matches[1]);
            if ($env === false) {
                throw new RuntimeException("Missing required environment variable " . $matches[1]);
            }
            return $env;
        }

        return $value;
    }
}
