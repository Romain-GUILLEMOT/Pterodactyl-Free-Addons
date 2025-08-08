<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers\Addons\Minecraft;

use Illuminate\Http\Request;
use phpDocumentor\Reflection\PseudoTypes\False_;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Models\Egg;
use Pterodactyl\Services\Addons\Minecraft\MinecraftVersionsService;
use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Eloquent\ServerVariableRepository;
use Pterodactyl\Services\Servers\ReinstallServerService;
use Pterodactyl\Repositories\Wings\DaemonFileRepository;
use stdClass;

class MinecraftVersionsController extends ClientApiController
{
    /**
     * @var MinecraftVersionsService
     */
    protected $minecraftVersionsService;
    /**
     * MinecraftVersionsController constructor.
     */
    public function __construct(MinecraftVersionsService $minecraftVersionsService, private ServerVariableRepository $variableRepository, private ReinstallServerService $reinstallServerService, private DaemonFileRepository $fileRepository) {
        parent::__construct();
        $this->minecraftVersionsService = $minecraftVersionsService;
        $this->variableRepository = $variableRepository;
        $this->reinstallServerService = $reinstallServerService;
        $this->fileRepository = $fileRepository;
    }
    
    /**
     * Returns a list of Minecraft versions based on the type.
     *
     * @param Request $request
     * @return array
     */
    public function index(Request $request): array
    {
        $versionsType = strtolower($request->query('type', 'vanilla'));
        $limit = (int) $request->query('limit', 100);
        $page = (int) $request->query('page', 1);
        $offset = ($page - 1) * $limit;
        $sortOrder = $request->query('sort', 'asc');
        $showSnapshot = $request->query('showSnapshot', false);
        $minecraftVersion = $request->query('minecraft_version', null);
        $versionRequired = false;
        if($versionsType === 'forge' || $versionsType === 'neoforge' || $versionsType === 'fabric' || $versionsType === 'mohist' || $versionsType === 'youer' || $versionsType === 'magmaneo') {
            $versionRequired = true;
        }
        $data = $this->minecraftVersionsService->getMinecraftReleases($versionsType, $minecraftVersion);
        if(empty($data)) {
            return [
                'success' => false,
                'data' => [],
                'message' => 'No versions found for the specified type (' . $versionsType . ').',
            ];
        }
        $isSnapshot = true;
        if(!$showSnapshot) {
            // Check if a element in the array that got "pre", "rc", "w", "experimental" or "snapshot" in name if yes set isSnapshot to true
            $isSnapshot = false;
            foreach ($data as $version) {
                if(preg_match('/(pre|rc|w|experimental|snapshot)/i', $version)) {
                    $isSnapshot = true;
                    break;
                }
            }
            //Remove all element in the array that got "pre", "rc", "w", "experimental" or "snapshot" in name
            $data = array_filter($data, function($version) {
                return !preg_match('/(pre|rc|w|experimental|snapshot)/i', $version);
            });
        } else {
            //Remove all element in the array that does not have "pre", "rc", "w", "experimental" or "snapshot" in name
            $data = array_filter($data, function($version) {
                return preg_match('/(pre|rc|w|experimental|snapshot)/i', $version);
            });
        }
        $lastPage = ceil(count($data) / $limit);
        if($sortOrder === 'desc') {
            $data = array_reverse($data);
        }
        $data = array_slice($data, $offset, $limit);
        return [
            'success' => true,
            'data' => $data,
            'isSnapshot' => $isSnapshot,
            'versionRequired' => $versionRequired,
            'meta' => [
                'total' => count($data),
                'limit' => $limit,
                'page' => $page,
                'lastPage' => $lastPage,
            ],
            'availableTypes' => [
                "vanilla",
                "spigot",
                "papermc",
                "purpur",
                "folia",
                "forge",
                "neoforge",
                "fabric",
                "quilt",
                "magmaneo",
                "mohist",
                "youer",
                "bungeecord",
                "velocity"
            ]
        ];
    }


    /**
     * Install Minecraft versions based on the type.
     *
     * TODO: Check MagmaNeo egg
     * TODO: Check Mohist egg
     * TODO: Controller -> Service
     * @param Server $server
     * @param Request $request
     * @return array
     */
    public function install(Server $server, Request $request): array
    {
        $type = strtolower($request->json('type', null));
        // Validate the server type
        if (!in_array($type, ["vanilla",
                "spigot",
                "papermc",
                "purpur",
                "folia",
                "forge",
                "neoforge",
                "fabric",
                "quilt",
                "magmaneo",
                "mohist",
                "youer",
                "bungeecord",
                "velocity"])) {
            return [
                'success' => false,
                'message' => 'Invalid server type specified.',
            ];
        }
        $minecraftVersion = $request->json('minecraft_version', null);
        // Validate the Minecraft version
        if (is_null($minecraftVersion)) {
            return [
                'success' => false,
                'message' => 'Invalid Minecraft version specified.',
                'data' => $minecraftVersion
            ];
        }
        $build = $request->json('build', null);
        $buildRequired = in_array($type, ['forge', 'neoforge', 'fabric', 'mohist', 'youer', 'magmaneo']);
        // Validate the build if the type requires it
        if ($buildRequired && is_null($build)) {
            return [
                'success' => false,
                'message' => 'Build is required for the selected type (' . $type . ').',
            ];
        }
        $deleteFiles = $request->json('deleteFiles', false);
        // Get the nessesary egg for the server

        $eggData = $this->getEggForType($type);
        if (!$eggData || !$eggData instanceof stdClass) {
            return [
                'success' => false,
                'message' => 'No egg found for the specified type (' . $type . ').',
            ];
        }
        $server->egg_id = $eggData->egg_id;
        $server->nest_id = $eggData->nest_id;

        //Docker image
        $parsedMinecraftVersion = implode('.', array_slice(explode('.', $minecraftVersion), 0, 2));

        $server->image = match (true) {
            version_compare($parsedMinecraftVersion, '1.20', '>=') => 'ghcr.io/pterodactyl/yolks:java_21',
            version_compare($parsedMinecraftVersion, '1.17', '>=') => 'ghcr.io/pterodactyl/yolks:java_17',
            version_compare($parsedMinecraftVersion, '1.16', '>=') => 'ghcr.io/pterodactyl/yolks:java_16', 
            version_compare($parsedMinecraftVersion, '1.12', '>=') => 'ghcr.io/pterodactyl/yolks:java_11',
            default => 'ghcr.io/pterodactyl/yolks:java_8',
        };
        
        // Server Startup

        $server->startup = $eggData->startupCommand;
        $server->save();

        // Server Variables
        $serverMcVersion = $server->variables()->where('env_variable', $eggData->minecraftVersion)->first();
        $serverMcBuild = $server->variables()->where('env_variable', $eggData->buildVariable)->first();
        if (!$serverMcVersion || (!$serverMcBuild && $buildRequired)) {
            return [
                'success' => false,
                'message' => 'No Minecraft version/build variable found for the specified type (' . $type . ').',
            ];
        }
        $this->variableRepository->updateOrCreate([
            'server_id' => $server->id,
            'variable_id' => $serverMcVersion->id,
        ], [
            'variable_value' => (string) $minecraftVersion,
        ]);
        if($buildRequired) {
            if($type === "forge") {
                $build = $minecraftVersion . '-' . $build;
            }
            $this->variableRepository->updateOrCreate([
                'server_id' => $server->id,
                'variable_id' => $serverMcBuild->id,
            ], [
                'variable_value' => (string) $build,
            ]);
        }

        // Delete old server.jar/lib folder
        $this->fileRepository->setServer($server)->deleteFiles('/', ['server.jar', 'libraries']);

        // Delete old server files if requested
        if ($deleteFiles) {
            $files = $this->fileRepository->setServer($server)->getDirectory('/');
            $filesToDelete = [];
            foreach ($files as $file) {
                $filesToDelete[] = $file['name'];
            }
            if(!empty($filesToDelete)) {
                $this->fileRepository->setServer($server)->deleteFiles('/', $filesToDelete);
            }
        }
        $this->reinstallServerService->handle($server);

        return [
            'success' => true,
            'message' => 'Installation in progress this can take few minutes.',
        ];
    }

    /**
     * Returns the correct egg for the provided type.
     *
     * @param string $type
     * @return array
     */
    private function getEggForType(string $type): stdClass | null
    {
        $eggName = "";
        $versionVariable = "";
        $buildVariable = "";
        switch ($type) {
            case "vanilla":
                $eggName = "Vanilla Minecraft";
                $versionVariable = "VANILLA_VERSION";
                break;
            case "papermc":
                $eggName = "Paper";
                $versionVariable = "MINECRAFT_VERSION";
                break;
            case "folia":
                $eggName = "Folia";
                $versionVariable = "MINECRAFT_VERSION";
                break;
            case "velocity":
                $eggName = "Velocity";
                $versionVariable = "VELOCITY_VERSION";
                break;
            case "fabric":
                $eggName = "Fabric";
                $versionVariable = "MC_VERSION";
                $buildVariable = "LOADER_VERSION";
                break;
            case "quilt":
                $eggName = "Quilt";
                $versionVariable = "MC_VERSION";
                break;
            case "forge":
                $eggName = "Forge Minecraft";
                $versionVariable = "MC_VERSION";
                $buildVariable = "FORGE_VERSION";
                break;
            case "spigot":
                $eggName = "Spigot";
                $versionVariable = "DL_VERSION";
                break;
            case "mohist":
                $eggName = "MohistMC";
                $versionVariable = "MC_VERSION";
                $buildVariable = "BUILD_NUMBER";
                break;
            case "youer":
                $eggName = "MohistMC";
                $versionVariable = "MC_VERSION";
                $buildVariable = "BUILD_NUMBER";
                break;
            case "neoforge":
                $eggName = "NeoForge";
                $versionVariable = "MC_VERSION";
                $buildVariable = "NEOFORGE_VERSION";
                break;
            case "bungeecord":
                $eggName = "Bungeecord";
                $versionVariable = "BUNGEE_VERSION";
                break;
            case "purpur":
                $eggName = "Purpur";
                $versionVariable = "MINECRAFT_VERSION";
                break;
            case "magmaneo":
                $eggName = "Magma";
                $versionVariable = "MC_VERSION";
                $buildVariable = "MAGMA_VERSION";
                break;
            default:
                $eggName = "";
        }
        $egg = Egg::where('name', $eggName)->first();

        if (!$egg) {
            return null;
        }
        $responseObject = new stdClass();
        $responseObject->egg_id = $egg->id;
        $responseObject->nest_id = $egg->nest_id;
        $responseObject->minecraftVersion = $versionVariable;
        $responseObject->buildVariable = $buildVariable;
        $responseObject->startupCommand = $egg->startup;
        return $responseObject;
    
}
}