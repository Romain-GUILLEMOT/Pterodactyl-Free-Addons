import { Allocation } from '@/api/server/getServer';
import http from '@/api/http';

export default async (
    uuid: string,
    type: string,
    minecraft_version: string,
    build: string,
    deleteFiles: boolean
): Promise<Allocation> => {
    const { data } = await http.post(`/api/client/servers/${uuid}/addons/minecraft/versions`, {
        type,
        minecraft_version,
        build,
        deleteFiles,
    });

    return data;
};
