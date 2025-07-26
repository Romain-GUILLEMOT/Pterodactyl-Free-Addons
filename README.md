## **Pterodactyl Version Changer Module 🚀**

Welcome to the dedicated README for the Pterodactyl Version Changer module!

This module provides a robust solution for dynamically managing Minecraft server versions directly through the **Pterodactyl Panel's client API**. It integrates seamlessly with Pterodactyl's system by adding new **Eggs and Nests** to facilitate the installation and switching of various Minecraft server types, including Vanilla, Paper, Forge, Fabric, and more.

---

### **API Client Usage**

The module exposes a new client API route to fetch available Minecraft versions.

**Endpoint:** `GET /api/client/servers/{server}/addons/minecraft/versions`

#### **Query Parameters**

| Parameter | Type | Default | Description |
| :-------- | :--- | :------ | :---------- |
| `page` | `int` | `1` | The result page to retrieve. |
| `limit` | `int` | `20` | The number of results per page. Max 50. |
| `search` | `string` | `null` | Filters versions by a search string (e.g., "1.18", "Paper"). |
| `type` | `string` | `null` | Filters by server type (e.g., "vanilla", "paper", "forge", "fabric"). |
| `stability` | `string` | `null` | Filters by version stability ("release", "snapshot", "beta", "alpha"). |
| `build` | `int` | `null` | Filters for a specific build number (e.g., Paper build 200). |
| `sort` | `string` | `"version"` | The field to sort by ("version", "release\_date", "type"). |
| `order` | `string` | `"desc"` | The sort order for versions (desc for descending, asc for ascending - default). |

#### **Example Response (Case: `type=paper` with `limit=3`)**

```json
[
  {
    "version": "1.20.4",
    "type": "paper",
    "stability": "release",
    "build": 412,
    "release_date": "2024-07-25T10:00:00Z",
    "download_url": "[https://papermc.io/api/v2/projects/paper/versions/1.20.4/builds/412/downloads/paper-1.20.4-412.jar](https://papermc.io/api/v2/projects/paper/versions/1.20.4/builds/412/downloads/paper-1.20.4-412.jar)"
  },
  {
    "version": "1.20.4",
    "type": "paper",
    "stability": "release",
    "build": 411,
    "release_date": "2024-07-24T18:30:00Z",
    "download_url": "[https://papermc.io/api/v2/projects/paper/versions/1.20.4/builds/411/downloads/paper-1.20.4-411.jar](https://papermc.io/api/v2/projects/paper/versions/1.20.4/builds/411/downloads/paper-1.20.4-411.jar)"
  },
  {
    "version": "1.20.4",
    "type": "paper",
    "stability": "release",
    "build": 410,
    "release_date": "2024-07-24T09:15:00Z",
    "download_url": "[https://papermc.io/api/v2/projects/paper/versions/1.20.4/builds/410/downloads/paper-1.20.4-410.jar](https://papermc.io/api/v2/projects/paper/versions/1.20.4/builds/410/downloads/paper-1.20.4-410.jar)"
  }
]
