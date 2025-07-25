<?php

namespace Pterodactyl\Services\Addons\Minecraft;

use Illuminate\Support\Facades\Http;
use DOMDocument; 
use DOMXPath;
use DOMElement; 

class MinecraftVersionsService
{

    /**
     * MinecraftVersionsService constructor.
     */
    public function __construct()
    {
    }

    public function getMinecraftReleases(string $type, string | null $minecraftVersion): array
    {
        switch ($type) {
            // Mojang API
            case 'vanilla':
                return $this->getVanillaRelease(false);
            case 'snapshot':
                return $this->getVanillaRelease(true);

            // PaperMC API
            case 'papermc':
                return $this->getPaperAPIReleases('paper');
            case 'folia':
                return $this->getPaperAPIReleases('folia');
            case 'velocity':
                return $this->getPaperAPIReleases('velocity');

            // Purpur API
            case 'purpur':
                return $this->getPurpurReleases();
            // Jenkins API
            case 'bungeecord':
                return $this->getBungeecordReleases();

            // Scrapping
            case 'forge':
                return $this->getForgeReleases($minecraftVersion);
            case 'quilt':
                return $this->getQuiltReleases();
            case 'neoforge':
                return $this->getNeoForgeReleases();
            case 'fabric':
                return $this->getFabricReleases(); 
            
            default:
                return [];
        }
    }
    /**
     * Get the Minecraft versions for vanilla releases and snapshots.
     *
     * @param string $type
     * @return array
     */
    public function getVanillaRelease(bool $snapshot): array
    {
        $versionsData = Http::get('https://launchermeta.mojang.com/mc/game/version_manifest.json');
        if(!$versionsData->successful()) {
            return [];
        }
        $versions = $versionsData->json()['versions'] ?? [];
        $filteredVersions = [];
        foreach ($versions as $version) {
            if ($version['type'] === "release" && !$snapshot) {
                $filteredVersions[] = [
                    'id' => $version['id'],
                ];
            } elseif ($version['type'] === "snapshot" && $snapshot) {
                $filteredVersions[] = [
                    'id' => $version['id'],
                ];
            }
        }
        return $filteredVersions;
    }

    /** 
     * Get the Minecraft versions for PaperMC and Folia releases.
     *
     * @return array
     */
    public function getPaperAPIReleases(string $type): array
    {
        $versionsData = Http::get("https://fill.papermc.io/v3/projects/$type");
        if(!$versionsData->successful()) {
            return [];
        }
        $versions = $versionsData->json()['versions'] ?? [];
        $filteredVersions = [];
        foreach ($versions as $majorVersion) {
            if(empty($majorVersion)) {
                continue;
            }
            $filteredVersions = array_merge($filteredVersions, array_map(function ($versionId) {
                return [
                    'id' => $versionId,
                ];
            }, $majorVersion));
        }
        return $filteredVersions;
    }
    /**
     * Get the Minecraft versions for Purpur releases.
     *
     * @return array
     */
    public function getPurpurReleases(): array
    {
        $versionsData = Http::get('https://api.purpurmc.org/v2/purpur/');
        if(!$versionsData->successful()) {
            return [];
        }
        $versions = $versionsData->json()['versions'] ?? [];
        $filteredVersions = [];
        foreach ($versions as $version) {
            $filteredVersions[] = [
                'id' => $version,
            ];
        }
        return array_reverse($filteredVersions);
    }

    /**
     * Get the Minecraft versions for BungeeCord releases.
     *
     * @return array
     */
    public function getBungeecordReleases(): array
    {
        $versionsData = Http::get('https://ci.md-5.net/job/BungeeCord/api/json?tree=allBuilds[number]');
        if(!$versionsData->successful()) {
            return [];
        }
        $builds = $versionsData->json()['allBuilds'] ?? [];
        $filteredVersions = [];
        foreach ($builds as $build) {
            $versionId = $build['number'] ?? '';
            if (empty($versionId)) {
                continue;
            }
            $filteredVersions[] = [
                'id' => $versionId,
            ];
        }
        return array_reverse($filteredVersions);
    }
   
    /**
     * Get the Minecraft versions for Forge releases.
     *
     * @return array
     */
    public function getForgeReleases(string | null $minecraftVersion): array
{
    $url = 'https://files.minecraftforge.net/net/minecraftforge/forge/';
    if ($minecraftVersion) {
        $url .= 'index_' . $minecraftVersion . '.html';
    } else {
        $url .= 'index.html';
    }

    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $htmlContent = @file_get_contents($url);

    if (!$htmlContent) {
        error_log("Failed to fetch URL: " . $url);
        return ['error' => 'Failed to fetch content from ' . $url];
    }

    $doc->loadHTML($htmlContent);
    libxml_clear_errors();

    $xpath = new DOMXPath($doc);
    $results = [];

    if ($minecraftVersion === null) {
        // CORRECTION: div au lieu d'aside
        $versionNodes = $xpath->query('//div[@class="sidebar-left sidebar-sticky"]//ul[contains(@class, "section-content")]//li[contains(@class, "li-version-list")]');
        
        foreach ($versionNodes as $versionNode) {
            // Tenter de récupérer le texte de la version principale (ex: "1.21")
            $mcVersionTextNode = $xpath->query('.//a[@class="elem-text toggle-collapsible"]/span[last()]', $versionNode)->item(0);
            $mcVersion = null;

            if ($mcVersionTextNode instanceof DOMElement) {
                $mcVersion = trim($mcVersionTextNode->textContent);
                // On peut retirer "Minecraft Version " si c'est présent, même si ce n'est pas le cas pour cet XPath précis
                $mcVersion = str_replace('Minecraft Version ', '', $mcVersion);
            }

            // Si pas de span, essayer de récupérer directement le texte
            if (!$mcVersion) {
                $mcVersionTextNode = $xpath->query('.//a[@class="elem-text toggle-collapsible"]/text()', $versionNode)->item(0);
                if ($mcVersionTextNode) {
                    $mcVersion = trim($mcVersionTextNode->nodeValue);
                }
            }

            // Si toujours pas de version, prendre le premier morceau de texte du li
            if (!$mcVersion) {
                $mcVersion = trim(explode("\n", $versionNode->textContent)[0]);
            }

            // Récupérer les sous-versions (ex: 1.21.8)
            $subVersionNodes = $xpath->query('.//ul[contains(@class, "nav-collapsible")]/li/a', $versionNode);
            $subVersions = [];
            foreach ($subVersionNodes as $subVersionLinkNode) {
                if ($subVersionLinkNode instanceof DOMElement) {
                    $subVersions[] = trim($subVersionLinkNode->textContent);
                }
            }


            // Récupérer aussi les li sans classe spécifique
            $otherSubVersionNodes = $xpath->query('.//ul[contains(@class, "nav-collapsible")]/li[not(@class) or @class="" or @class="elem-active"]', $versionNode);
            foreach ($otherSubVersionNodes as $otherSubVersionNode) {
                if ($otherSubVersionNode instanceof DOMElement) {
                    $subVersionText = trim($otherSubVersionNode->textContent);
                    if ($subVersionText) {
                        $results[] = $subVersionText;
                    }
                }
            }
            
        }
    } else {
            $buildVersionNodes = $xpath->query('//td[@class="download-version"]');
            foreach ($buildVersionNodes as $tdNode) { 
                if ($tdNode instanceof DOMElement) {
                    $results[] = trim($tdNode->nodeValue);
                }
            }
        
    }
    
    return $results;
}


    /**
     * Get the Minecraft versions for Quilt releases.
     *
     * @return array
     */
    public function getQuiltReleases(): array
    {
        // Implement the logic to retrieve Quilt versions
        return [];  
    }
    /**
     * Get the Minecraft versions for NeoForge releases.
     *
     * @return array
     */
    public function getNeoForgeReleases(): array
    {
        // Implement the logic to retrieve NeoForge versions
        return [];
    }
    /**
     * Get the Minecraft versions for Fabric releases.
     *
     * @return array
     */
    public function getFabricReleases(): array
    {
        // Implement the logic to retrieve Fabric versions
        return [];
    }
   
}
