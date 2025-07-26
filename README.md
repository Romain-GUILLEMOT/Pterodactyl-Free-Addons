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
| `limit` | `int` | `100` | The number of results per page. |
| `type` | `string` | `"vanilla"` | Filters by server type (e.g., "vanilla", "paper", "forge", "fabric", "papermc", "folia", "velocity", "quilt", "spigot", "mohist", "youer", "neoforge", "bungeecord", "purpur", "magmaneo"). |
| `sort` | `string` | `"asc"` | The sort order for versions (`asc` for ascending, `desc` for descending). *Note: This parameter controls the overall order of the results, not sorting by specific fields.* |
| `minecraft_version` | `string` | `null` | Required for `forge`, `neoforge`, `fabric`, `mohist`, `youer`, `magmaneo` to get specific builds/loaders for a Minecraft version. |

#### **Example Response (Case: `type=papermc` with `limit=3`)**

```json
{
  "success": true,
  "data": [
    {
      "id": "1.20.4"
    },
    {
      "id": "1.20.3"
    },
    {
      "id": "1.20.2"
    }
  ],
  "versionRequired": false,
  "meta": {
    "total": 123, // Example: Total number of available PaperMC versions
    "limit": 3,
    "page": 1
  }
}
```

#### **Example Response (Case: `type=fabric` with `minecraft_version=1.20.4`)**

```json
{
  "success": true,
  "data": {
    "game": [
      "1.20.4"
    ],
    "loader": [
      "0.15.11",
      "0.15.10",
      "0.15.9"
    ]
  },
  "versionRequired": true,
  "meta": {
    "total": 1, // Total refers to the number of game versions returned, not loaders
    "limit": 100, // Limit is ignored when versionRequired is true
    "page": 1
  }
}
```

#### **Example Response (Case: `type=forge` with `minecraft_version=1.20.4`)**

```json
{
  "success": true,
  "data": [
    "49.0.32",
    "49.0.31",
    "49.0.30"
  ],
  "versionRequired": true,
  "meta": {
    "total": 50, // Example: Total number of Forge builds for 1.20.4
    "limit": 100, // Limit is ignored when versionRequired is true
    "page": 1
  }
}
