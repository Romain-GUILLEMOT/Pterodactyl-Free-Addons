<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers\Addons\Minecraft;

use Illuminate\Http\Request;
use phpDocumentor\Reflection\PseudoTypes\False_;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Services\Addons\Minecraft\MinecraftVersionsService;

class MinecraftVersionsController extends ClientApiController
{
    /**
     * @var MinecraftVersionsService
     */
    protected $minecraftVersionsService;
    /**
     * MinecraftVersionsController constructor.
     */
    public function __construct(MinecraftVersionsService $minecraftVersionsService) {
        parent::__construct();
        $this->minecraftVersionsService = $minecraftVersionsService;
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
        $limit = $request->query('limit', 100);
        $page = $request->query('page', 1);
        $offset = ($page - 1) * $limit;
        $sortOrder = $request->query('sort', 'asc');
        $minecraftVersion = $request->query('minecraft_version', null);
        $versionRequired = false;
        if($versionsType === 'forge' || $versionsType === 'neoforge' || $versionsType === 'fabric') {
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

        
        if($sortOrder === 'desc') {
            $data = array_reverse($data);
        }
        if(!$versionRequired) {
            $data = array_slice($data, $offset, $limit);
        }

        
        return [
            'success' => true,
            'data' => $data,
            'versionRequired' => $versionRequired,
            'meta' => [
                'total' => count($data),
                'limit' => $limit,
                'page' => $page,
            ],
        ];
    }

    
}