import useSWR from 'swr';
import http from '@/api/http';

interface MinecraftVersionData {
    success: boolean;
    versionRequired: boolean;
    meta: {
        total: number;
        limit: number;
        page: number;
        lastPage: number;
    };
    availableTypes: string[];
    isSnapshot: boolean;
    data?: string[];
    message?: string;
}

export default (
    uuid: string,
    type: string,
    minecraft_version: string | null,
    page: number,
    limit: number,
    sortOrder: string,
    showSnapshot: boolean
) => {
    const { data, error, isValidating, mutate } = useSWR<MinecraftVersionData>(
        [uuid, '/addons/minecraft/versions', type, minecraft_version, page, limit, sortOrder, showSnapshot],
        async (): Promise<MinecraftVersionData> => {
            const { data } = await http.get(
                `/api/client/servers/${uuid}/addons/minecraft/versions?type=${type}&page=${page}&limit=${limit}&sort=${sortOrder}
                ${showSnapshot ? '&showSnapshot=true' : ''}
                ${minecraft_version ? `&minecraft_version=${minecraft_version}` : ''}`
            );
            return data;
        },
        { errorRetryCount: 3 }
    );

    return { data, error, isValidating, mutate };
};
