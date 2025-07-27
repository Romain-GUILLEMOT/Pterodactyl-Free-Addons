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

    public function getMinecraftReleases(
        string $type,
        string|null $minecraftVersion
    ): array {
        switch ($type) {
            // Mojang API
            case "vanilla":
                return $this->getVanillaRelease();
                
            // PaperMC API
            case "papermc":
                return $this->getPaperAPIReleases("paper");
            case "folia":
                return $this->getPaperAPIReleases("folia");
            case "velocity":
                return $this->getPaperAPIReleases("velocity");

            //Fabric API Structure
            case "fabric":
                return $this->getFabricReleases($minecraftVersion);
            case "quilt":
                return $this->getQuiltReleases();

            // Scrapping
            case "forge":
                return $this->getForgeReleases($minecraftVersion);
            case "spigot":
                return $this->getSpigotReleases();

            // Mohist API
            case "mohist":
                return $this->getMohistReleases($minecraftVersion, 'mohist');
            case "youer":
                return $this->getMohistReleases($minecraftVersion, 'youer');
            
            //Other APIs
            case "neoforge":
                return $this->getNeoForgeReleases($minecraftVersion);
            case "bungeecord":
                return $this->getBungeecordReleases();
            case "purpur":
                return $this->getPurpurReleases();
            case "magmaneo":
                return $this->getMagmaNeoReleases($minecraftVersion);
            default:
                return [];
        }


        //TODO: Add Bedrock versions (In future release)
    }

    // Mojang API

    /**
     * Get the Minecraft versions for vanilla releases and snapshots.
     *
     * @param string $type
     * @return array
     */
    public function getVanillaRelease(): array
    {
        $versionsData = Http::get(
            "https://launchermeta.mojang.com/mc/game/version_manifest.json"
        );
        if (!$versionsData->successful()) {
            return [];
        }
        $versions = $versionsData->json()["versions"] ?? [];
        $filteredVersions = [];
        foreach ($versions as $version) {
            $filteredVersions[] = $version["id"];
        }
        return $filteredVersions;
    }

    // PaperMC API

    /**
     * Get the Minecraft versions for PaperMC and Folia releases.
     *
     * @return array
     */
    public function getPaperAPIReleases(string $type): array
    {
        $versionsData = Http::get("https://fill.papermc.io/v3/projects/$type");
        if (!$versionsData->successful()) {
            return [];
        }
        $versions = $versionsData->json()["versions"] ?? [];
        $filteredVersions = [];
        foreach ($versions as $majorVersion) {
            if (empty($majorVersion)) {
                continue;
            }
            $filteredVersions = array_merge(
                $filteredVersions,
                array_map(function ($versionId) {
                    return $versionId;
                }, $majorVersion)
            );
        }
        return $filteredVersions;
    }

    // Fabric API structure

    /**
     * Get the Minecraft versions for Fabric releases.
     *
     * @return array
     */
    public function getFabricReleases(string|null $minecraftVersion): array
    {   
                $filteredVersions = [];

        if(!$minecraftVersion) {
             $versionsData = Http::get("https://meta.fabricmc.net/v2/versions/game");
            if (!$versionsData->successful()) {
                return [];
            }

            $versions = $versionsData->json() ?? [];
            foreach ($versions as $version) {
                if (isset($version["version"])) {
                    $filteredVersions[] = $version["version"];
                }
            }
        } else {
$loaderData = Http::get("https://meta.fabricmc.net/v2/versions/loader");
        if (!$loaderData->successful()) {
            return [];
        }
        $loaders = $loaderData->json() ?? [];
        foreach ($loaders as $version) {
            if (isset($version["version"])) {
                $filteredVersions[] = $version["version"];
            }
        }
        }
       
        
        return $filteredVersions;
    }

    /**
     * Get the Minecraft versions for Quilt releases.
     *
     * @return array
     */
    public function getQuiltReleases(): array
    {
        $versionsData = Http::get("https://meta.quiltmc.org/v3/versions/game");
        if (!$versionsData->successful()) {
            return [];
        }

        $versions = $versionsData->json() ?? [];
        $filteredVersions = [];
        foreach ($versions as $version) {
            if (isset($version["version"])) {
                $filteredVersions[] = $version["version"];
            }
        }

        return $filteredVersions;
    }

    // Scrapping

    /**
     * Get the Minecraft versions for Forge releases.
     *
     * @return array
     */
    public function getForgeReleases(string|null $minecraftVersion): array
    {
        $url = "https://files.minecraftforge.net/net/minecraftforge/forge/";
        if ($minecraftVersion) {
            $url .= "index_" . $minecraftVersion . ".html";
        } else {
            $url .= "index.html";
        }

        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $htmlContent = @file_get_contents($url);

        if (!$htmlContent) {
            error_log("Failed to fetch URL: " . $url);
            return ["error" => "Failed to fetch content from " . $url];
        }

        $doc->loadHTML($htmlContent);
        libxml_clear_errors();

        $xpath = new DOMXPath($doc);
        $results = [];

        if ($minecraftVersion === null) {
            // CORRECTION: div au lieu d'aside
            $versionNodes = $xpath->query(
                '//div[@class="sidebar-left sidebar-sticky"]//ul[contains(@class, "section-content")]//li[contains(@class, "li-version-list")]'
            );

            foreach ($versionNodes as $versionNode) {
                // Tenter de récupérer le texte de la version principale (ex: "1.21")
                $mcVersionTextNode = $xpath
                    ->query(
                        './/a[@class="elem-text toggle-collapsible"]/span[last()]',
                        $versionNode
                    )
                    ->item(0);
                $mcVersion = null;

                if ($mcVersionTextNode instanceof DOMElement) {
                    $mcVersion = trim($mcVersionTextNode->textContent);
                    // On peut retirer "Minecraft Version " si c'est présent, même si ce n'est pas le cas pour cet XPath précis
                    $mcVersion = str_replace(
                        "Minecraft Version ",
                        "",
                        $mcVersion
                    );
                }

                // Si pas de span, essayer de récupérer directement le texte
                if (!$mcVersion) {
                    $mcVersionTextNode = $xpath
                        ->query(
                            './/a[@class="elem-text toggle-collapsible"]/text()',
                            $versionNode
                        )
                        ->item(0);
                    if ($mcVersionTextNode) {
                        $mcVersion = trim($mcVersionTextNode->nodeValue);
                    }
                }

                // Si toujours pas de version, prendre le premier morceau de texte du li
                if (!$mcVersion) {
                    $mcVersion = trim(
                        explode("\n", $versionNode->textContent)[0]
                    );
                }

                // Récupérer les sous-versions (ex: 1.21.8)
                $subVersionNodes = $xpath->query(
                    './/ul[contains(@class, "nav-collapsible")]/li/a',
                    $versionNode
                );
                $subVersions = [];
                foreach ($subVersionNodes as $subVersionLinkNode) {
                    if ($subVersionLinkNode instanceof DOMElement) {
                        $subVersions[] = trim($subVersionLinkNode->textContent);
                    }
                }

                // Récupérer aussi les li sans classe spécifique
                $otherSubVersionNodes = $xpath->query(
                    './/ul[contains(@class, "nav-collapsible")]/li[not(@class) or @class="" or @class="elem-active"]',
                    $versionNode
                );
                foreach ($otherSubVersionNodes as $otherSubVersionNode) {
                    if ($otherSubVersionNode instanceof DOMElement) {
                        $subVersionText = trim(
                            $otherSubVersionNode->textContent
                        );
                        if ($subVersionText) {
                            $results[] = $subVersionText;
                        }
                    }
                }
            }
        } else {
            $buildVersionNodes = $xpath->query(
                '//td[@class="download-version"]'
            );
            foreach ($buildVersionNodes as $tdNode) {
                if ($tdNode instanceof DOMElement) {
                    $results[] = trim($tdNode->nodeValue);
                }
            }
        }

        return $results;
    }

     /**
     * Get the Minecraft versions for Spigot releases.
     *
     * @return array
     */
    public function getSpigotReleases(): array
    {
        $url = 'https://hub.spigotmc.org/versions/';
        
        $response = Http::get($url); 
        
        if (!$response->successful()) {
            return [];
        }
        $htmlContent = $response->body();

        $doc = new DOMDocument();
        @$doc->loadHTML($htmlContent); 
        $xpath = new DOMXPath($doc);

        $rawVersions = [];

        $links = $xpath->query('//a[contains(@href, ".json")]');

        foreach ($links as $link) {
            if ($link instanceof DOMElement) { 
                $href = $link->getAttribute('href');
                if (preg_match('/^(\d+\.\d+(?:\.\d+)?(?:-[a-zA-Z0-9.]+)?)\.json$/', $href, $matches)) {
                    $rawVersions[] = $matches[1]; 
                }
            }
        }

        $filteredVersions = array_filter($rawVersions, function($version) {
            return str_starts_with($version, '1.');
        });
        usort($filteredVersions, 'version_compare');
        return array_reverse($filteredVersions);
    }

    // Mohist API

    /**
     * Get the Minecraft versions for Mohist releases.
     *
     * @param string|null $minecraftVersion
     * @return array
     */
    public function getMohistReleases(string|null $minecraftVersion, string $type): array
    {
        $filteredVersions = [];
        if(!$minecraftVersion) {
            $response = Http::get("https://api.mohistmc.com/project/$type/versions");
            if (!$response->successful()) {
                return [];
            }
            $versions = $response->json() ?? [];
            foreach ($versions as $version) {
                if (str_starts_with($version["name"], $minecraftVersion)) {
                    $filteredVersions[] = $version["name"];
                }
            }
            usort($filteredVersions, function($a, $b) {
                return version_compare($b, $a); 
            });
        } else {
            $response = Http::get("https://api.mohistmc.com/project/$type/$minecraftVersion/builds");
            if (!$response->successful()) {
                return [];
            }
            $builds = $response->json() ?? [];
            foreach ($builds as $build) {
                if(isset($build["id"])) {
                $filteredVersions[] = "{$build["id"]}";
                }
            }
        }

        return $filteredVersions;
    }

    // Other APIs

    /**
     * Get the Minecraft versions for NeoForge releases.
     *
     * @return array
     */
    public function getNeoForgeReleases(string|null $minecraftVersion): array
    {
        $versionsData = Http::get(
            "https://maven.neoforged.net/api/maven/versions/releases/net/neoforged/neoforge"
        );
        if (!$versionsData->successful()) {
            return [];
        }
        $versions = $versionsData->json()["versions"] ?? [];
        $filteredVersions = [];
        if (!$minecraftVersion) {
            foreach ($versions as $version) {
                if (str_contains($version, "25w14craftmine")) {
                    continue;
                }
                $parts = explode(".", $version);
                if (count($parts) < 3) {
                    continue;
                }
                $version = "1." . $parts[0] . "." . $parts[1];
                if (in_array($version, $filteredVersions)) {
                    continue;
                }
                $filteredVersions[] = $version;
            }
        } else {
            foreach ($versions as $version) {
                if (str_contains($version, "25w14craftmine")) {
                    continue;
                }
                $parsedMcVersion = explode(".", $minecraftVersion);
                if (count($parsedMcVersion) < 3) {
                    continue;
                }

                $parsedMcVersion =
                    $parsedMcVersion[1] . "." . $parsedMcVersion[2];
                if (str_starts_with($version, $parsedMcVersion)) {
                    $filteredVersions[] = $version;
                }
            }
        }
        return $filteredVersions;
    }

    /**
     * Get the Minecraft versions for BungeeCord releases.
     *
     * @return array
     */
    public function getBungeecordReleases(): array
    {
        $versionsData = Http::get(
            "https://ci.md-5.net/job/BungeeCord/api/json?tree=allBuilds[number]"
        );
        if (!$versionsData->successful()) {
            return [];
        }
        $builds = $versionsData->json()["allBuilds"] ?? [];
        $filteredVersions = [];
        foreach ($builds as $build) {
            $versionId = $build["number"] ?? "";
            if (empty($versionId)) {
                continue;
            }
            $filteredVersions[] = $versionId;
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
        $versionsData = Http::get("https://api.purpurmc.org/v2/purpur/");
        if (!$versionsData->successful()) {
            return [];
        }
        $versions = $versionsData->json()["versions"] ?? [];
        $filteredVersions = [];
        foreach ($versions as $version) {
            $filteredVersions[] = $version;
        }
        return array_reverse($filteredVersions);
    }

    /**
     * Get the Minecraft versions for Magma releases.
     *
     * @param string|null $minecraftVersion
     * @return array
     */
    public function getMagmaNeoReleases(string|null $minecraftVersion): array
    {
        $versionsData = Http::get("https://magmafoundation.org/api/versions?limit=0");
        if (!$versionsData->successful()) {
            return [];
        }
        $versions = $versionsData->json() ?? [];
        $filteredVersions = [];
        if(!$minecraftVersion) {
            foreach ($versions['versions'] as $version) {
                $version = $version['version'] ?? '';
                $parts = explode(".", $version);
                if (count($parts) < 3) {
                    continue;
                }
                $version = "1." . $parts[0] . "." . $parts[1];
                if (in_array($version, $filteredVersions)) {
                    continue;
                }
                $filteredVersions[] = $version;
            }
            usort($filteredVersions, function($a, $b) {
                return version_compare($b, $a); 
            });
        } else {
            foreach ($versions['versions'] as $version) {
               $version = $version['version'] ?? '';
                $parsedMcVersion = explode(".", $minecraftVersion);
                if (count($parsedMcVersion) < 3) {
                    continue;
                }

                $parsedMcVersion =
                    $parsedMcVersion[1] . "." . $parsedMcVersion[2];
                if (str_starts_with($version, $parsedMcVersion)) {
                    $filteredVersions[] = $version;
                }
            }
        }
        return $filteredVersions;
    }

   
}