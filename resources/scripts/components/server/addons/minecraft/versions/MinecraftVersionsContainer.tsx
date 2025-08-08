import { httpErrorToHuman } from '@/api/http';
import getMinecraftVersions from '@/api/server/addons/minecraft/versions/getMinecraftVersions';
import { Alert } from '@/components/elements/alert';
import Button from '@/components/elements/button/Button';
import { ServerError } from '@/components/elements/ScreenBlock';
import Spinner from '@/components/elements/Spinner';
import Switch from '@/components/elements/Switch';
import { ServerContext } from '@/state/server';
import React, { useEffect, useState } from 'react';
import InstallConfirmationDialog from './InstallConfirmationDialog';
import installMinecraftVersion from '@/api/server/addons/minecraft/versions/installMinecraftVersion';
import { de } from 'date-fns/locale';

export default function MinecraftVersionsContainer() {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const [type, setType] = useState('vanilla');
    const [minecraftVersion, setMinecraftVersion] = useState<string | null>(null);
    const [availableTypes, setAvailableTypes] = useState<string[]>([]);
    const [versionRequired, setVersionRequired] = useState<boolean>(false);
    const [selectedBuild, setSelectedBuild] = useState<string | null>(null);
    const [showSnapshot, setShowSnapshot] = useState(false);
    const [page, setPage] = useState(1);
    const [sortOrder, setSortOrder] = useState('asc');
    const [limit, setLimit] = useState(48);
    const [showConfirmDialog, setShowConfirmDialog] = useState(false);
    const [isInstalling, setIsInstalling] = useState(false);

    const { data, isValidating, error, mutate } = getMinecraftVersions(
        uuid,
        type,
        versionRequired ? minecraftVersion : null,
        page,
        limit,
        sortOrder,
        showSnapshot
    );
    useEffect(() => {
        if (data && data.availableTypes) {
            setAvailableTypes(data.availableTypes);
        }
        if (data && data.versionRequired !== undefined) {
            setVersionRequired(data.versionRequired);
        }
    }, [data]);
    useEffect(() => {
        setPage(1);
        if (type === 'bungeecord') {
            setLimit(30);
        } else {
            setLimit(48);
        }
        setMinecraftVersion(null);
        setSelectedBuild(null);
    }, [type]);

    const handleInstallClick = () => {
        setShowConfirmDialog(true);
    };
    const handleConfirmInstall = async (deleteFiles: boolean) => {
        setIsInstalling(true);
        try {
            if (!minecraftVersion) {
                throw new Error('Please select a Minecraft version before installing.');
            }
            await installMinecraftVersion(uuid, type, minecraftVersion, selectedBuild || '', deleteFiles);

            setShowConfirmDialog(false);
            // Optionnel : afficher un message de succès ou rediriger
        } catch (error) {
            console.error('Installation failed:', error);
            // Gérer l'erreur
        } finally {
            setIsInstalling(false);
        }
    };

    const handleCancelInstall = () => {
        if (!isInstalling) {
            setShowConfirmDialog(false);
        }
    };

    if (isValidating || !data) {
        return (
            <div className='flex flex-col h-full'>
                <div className='flex-1 overflow-y-auto p-4'>
                    <div className='grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:flex lg:justify-between lg:gap-2 gap-3'>
                        {availableTypes.map((availableType) => (
                            <div
                                key={availableType}
                                className={`
            relative rounded-lg border-2 cursor-pointer transition-all duration-300 aspect-square flex flex-col items-center justify-center p-3 lg:flex-1 lg:max-w-none
            hover:scale-105 hover:shadow-lg
            ${
                type === availableType
                    ? 'border-blue-500 bg-blue-900 shadow-md'
                    : 'border-gray-600 bg-gray-800 hover:border-gray-400'
            }
        `}
                                onClick={() => setType(availableType)}
                            >
                                {/* Logo en haut */}
                                <div className='mb-2'>
                                    <img
                                        src={`/assets/addons/minecraft/versions/${availableType}.webp`}
                                        alt={availableType}
                                        className='w-8 h-8 object-contain'
                                        onError={(e) => {
                                            e.currentTarget.src = '/assets/addons/minecraft/versions/default.webp';
                                        }}
                                    />
                                </div>

                                {/* Texte en bas */}
                                <span className='text-white text-xs font-medium capitalize text-center'>
                                    {availableType}
                                </span>

                                {/* Effet de sélection */}
                                {type === availableType && (
                                    <div className='absolute top-1 right-1 w-2 h-2 bg-blue-400 rounded-full animate-pulse'></div>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
                <Spinner centered size={Spinner.Size.LARGE} />
            </div>
        );
    }

    if (error) {
        return <ServerError title={'Oops!'} message={httpErrorToHuman(error)} onRetry={() => mutate()} />;
    }
    if (!data.success) {
        return (
            <div className='flex flex-col h-full'>
                <div className='flex-1 overflow-y-auto p-4'>
                    <div className='grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:flex lg:justify-between lg:gap-2 gap-3'>
                        {availableTypes.map((availableType) => (
                            <div
                                key={availableType}
                                className={`
            relative rounded-lg border-2 cursor-pointer transition-all duration-300 aspect-square flex flex-col items-center justify-center p-3 lg:flex-1 lg:max-w-none
            hover:scale-105 hover:shadow-lg
            ${
                type === availableType
                    ? 'border-blue-500 bg-blue-900 shadow-md'
                    : 'border-gray-600 bg-gray-800 hover:border-gray-400'
            }
        `}
                                onClick={() => setType(availableType)}
                            >
                                {/* Logo en haut */}
                                <div className='mb-2'>
                                    <img
                                        src={`/assets/addons/minecraft/versions/${availableType}.webp`}
                                        alt={availableType}
                                        className='w-8 h-8 object-contain'
                                        onError={(e) => {
                                            e.currentTarget.src = '/assets/addons/minecraft/versions/default.webp';
                                        }}
                                    />
                                </div>

                                {/* Texte en bas */}
                                <span className='text-white text-xs font-medium capitalize text-center'>
                                    {availableType}
                                </span>

                                {/* Effet de sélection */}
                                {type === availableType && (
                                    <div className='absolute top-1 right-1 w-2 h-2 bg-blue-400 rounded-full animate-pulse'></div>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
                <ServerError
                    title={'Error'}
                    message={data.message || 'An error occurred while fetching Minecraft versions.'}
                />
            </div>
        );
    }
    return (
        <div className='flex flex-col h-full'>
            <div className='flex-1 overflow-y-auto p-4'>
                <div className='grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:flex lg:justify-between lg:gap-2 gap-3'>
                    {availableTypes.map((availableType) => (
                        <div
                            key={availableType}
                            className={`
            relative rounded-lg border-2 cursor-pointer transition-all duration-300 aspect-square flex flex-col items-center justify-center p-3 lg:flex-1 lg:max-w-none
            hover:scale-105 hover:shadow-lg
            ${
                type === availableType
                    ? 'border-blue-500 bg-blue-900 shadow-md'
                    : 'border-gray-600 bg-gray-800 hover:border-gray-400'
            }
        `}
                            onClick={() => setType(availableType)}
                        >
                            {/* Logo en haut */}
                            <div className='mb-2'>
                                <img
                                    src={`/assets/addons/minecraft/versions/${availableType}.webp`}
                                    alt={availableType}
                                    className='w-8 h-8 object-contain'
                                    onError={(e) => {
                                        e.currentTarget.src = '/assets/addons/minecraft/versions/default.webp';
                                    }}
                                />
                            </div>

                            {/* Texte en bas */}
                            <span className='text-white text-xs font-medium capitalize text-center'>
                                {availableType}
                            </span>

                            {/* Effet de sélection */}
                            {type === availableType && (
                                <div className='absolute top-1 right-1 w-2 h-2 bg-blue-400 rounded-full animate-pulse'></div>
                            )}
                        </div>
                    ))}
                </div>
            </div>
            {!data.versionRequired ? (
                <div className='border-t border-gray-700 bg-gray-800'>
                    <div className='p-4'>
                        <h3 className='text-sm font-medium text-gray-200 mb-3 flex items-center'>
                            <span className='capitalize'>{`${type} Versions`}</span>
                            {data.isSnapshot && (
                                <div className='ml-auto flex items-center'>
                                    <Switch
                                        name='showSnapshot'
                                        label='Show Snapshots'
                                        defaultChecked={showSnapshot}
                                        onChange={(e) => {
                                            setShowSnapshot(e.target.checked);
                                            setPage(1); // Reset to first page when toggling snapshots
                                        }}
                                    />
                                </div>
                            )}
                        </h3>
                        {type === 'bungeecord' && (
                            <Alert type='warning' className='mb-4'>
                                <div>
                                    <strong>BungeeCord - Minecraft Compatibility</strong>
                                    <br />
                                    <span className='text-sm'>
                                        BungeeCord uses build numbers. Here&apos;s the compatibility chart:
                                    </span>
                                    <br />
                                    • Build &gt; 1119 : Minecraft 1.8+
                                    <br />
                                    • Build 702-1119 : Minecraft ≤ 1.7.10
                                    <br />
                                    • Build 667-701 : Minecraft ≤ 1.6.4
                                    <br />
                                    • Build 549-666 : Minecraft ≤ 1.6.2
                                    <br />
                                    • Build 387-548 : Minecraft ≤ 1.5.2
                                    <br />• Build 0-251 : Minecraft ≤ 1.4.7
                                </div>
                            </Alert>
                        )}

                        <div className='grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-2'>
                            {data.data?.map((version) => (
                                <div
                                    key={version}
                                    className={`
                            relative p-3 border rounded cursor-pointer transition-all duration-200
                            ${
                                minecraftVersion === version
                                    ? 'border-blue-500 bg-blue-900'
                                    : 'border-gray-600 bg-gray-700 hover:border-gray-500 hover:bg-gray-600'
                            }
                        `}
                                    onClick={() => setMinecraftVersion(version)}
                                >
                                    <div className='text-center'>
                                        <div className='text-xs font-medium text-gray-300 capitalize mb-1'>
                                            {type.charAt(0).toUpperCase() + type.slice(1)} Version
                                        </div>
                                        <div className='text-sm font-semibold text-white'>{version}</div>
                                    </div>
                                    {minecraftVersion === version && (
                                        <div className='absolute top-1 right-1 w-2 h-2 bg-blue-400 rounded-full animate-pulse'></div>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            ) : (
                <div className='border-t border-gray-700 bg-gray-800'>
                    {/* Étape 1: Sélection de la version Minecraft */}
                    {!minecraftVersion ? (
                        <div className='p-4'>
                            <div className='flex'>
                                <div>
                                    <h3 className='text-sm font-medium text-gray-200 mb-3'>
                                        Select Minecraft Version for {type.charAt(0).toUpperCase() + type.slice(1)}
                                    </h3>
                                    <p className='text-xs text-gray-400 mb-4'>
                                        Choose the Minecraft version to see available {type} builds.
                                    </p>
                                </div>
                                <div className='ml-auto flex items-center'>
                                    {data.isSnapshot && (
                                        <div className='ml-auto flex items-center'>
                                            <Switch
                                                name='showSnapshot'
                                                label='Show Snapshots'
                                                defaultChecked={showSnapshot}
                                                onChange={(e) => {
                                                    setShowSnapshot(e.target.checked);
                                                    setPage(1); // Reset to first page when toggling snapshots
                                                }}
                                            />
                                        </div>
                                    )}
                                </div>
                            </div>

                            <div className='grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-2'>
                                {/* Liste des versions Minecraft communes */}
                                {data.data?.map((version) => (
                                    <div
                                        key={version}
                                        className={`
                                relative p-3 border rounded cursor-pointer transition-all duration-200
                                border-gray-600 bg-gray-700 hover:border-gray-500 hover:bg-gray-600
                            `}
                                        onClick={() => setMinecraftVersion(version)}
                                    >
                                        <div className='text-center'>
                                            <div className='text-xs font-medium text-gray-300 mb-1'>
                                                {type.charAt(0).toUpperCase() + type.slice(1)} Version
                                            </div>
                                            <div className='text-sm font-semibold text-white'>{version}</div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    ) : (
                        /* Étape 2: Affichage des builds pour la version sélectionnée */
                        <div className='p-4'>
                            <div className='flex items-center justify-between mb-3'>
                                <h3 className='text-sm font-medium text-gray-200'>
                                    {type.charAt(0).toUpperCase() + type.slice(1)} Builds for Minecraft{' '}
                                    {minecraftVersion}
                                </h3>
                                {data.isSnapshot && (
                                    <div className='ml-auto flex items-center'>
                                        <Switch
                                            name='showSnapshot'
                                            label='Show Snapshots'
                                            defaultChecked={showSnapshot}
                                            onChange={(e) => {
                                                setShowSnapshot(e.target.checked);
                                                setPage(1); // Reset to first page when toggling snapshots
                                            }}
                                        />
                                    </div>
                                )}
                                <Button.Text
                                    className='text-xs text-blue-400 hover:text-blue-300'
                                    onClick={() => setMinecraftVersion(null)}
                                >
                                    ← Back to version selection
                                </Button.Text>
                            </div>

                            <div className='grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-2'>
                                {data.data?.map((build) => (
                                    <div
                                        key={build}
                                        className={`
                                relative p-3 border rounded cursor-pointer transition-all duration-200
                                ${
                                    selectedBuild === build
                                        ? 'border-blue-500 bg-blue-900'
                                        : 'border-gray-600 bg-gray-700 hover:border-gray-500 hover:bg-gray-600'
                                }
                            `}
                                        onClick={() => setSelectedBuild(build)}
                                    >
                                        <div className='text-center'>
                                            <div className='text-xs font-medium text-gray-300 mb-1'>
                                                {type.charAt(0).toUpperCase() + type.slice(1)} Build
                                            </div>
                                            <div className='text-sm font-semibold text-white'>{build}</div>
                                        </div>
                                        {selectedBuild === build && (
                                            <div className='absolute top-1 right-1 w-2 h-2 bg-blue-400 rounded-full animate-pulse'></div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            )}
            <div className='border-t border-gray-700 mt-4 pt-3 flex justify-between items-center'>
                <div className='flex items-center space-x-3'>
                    <div className='flex items-center space-x-2'>
                        <Button.Text
                            disabled={page === 1}
                            className='ml-2 px-3 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-150'
                            onClick={() => setPage(page - 1)}
                        >
                            ← Previous
                        </Button.Text>

                        <span className='text-sm text-gray-400'>
                            Page {page} / {data.meta.lastPage || 1}
                        </span>

                        <Button.Text
                            disabled={!data.data || data.data.length < limit}
                            className='px-3 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-150'
                            onClick={() => setPage(page + 1)}
                        >
                            Next →
                        </Button.Text>
                    </div>

                    <div className='flex items-center space-x-2'>
                        <span className='text-sm text-gray-400'>Show:</span>
                        <select
                            value={limit}
                            onChange={(e) => {
                                setLimit(Number(e.target.value));
                                setPage(1);
                            }}
                            className='px-2 py-1 bg-gray-700 border border-gray-600 rounded text-white text-sm focus:border-blue-500 focus:outline-none'
                        >
                            <option value={12}>12</option>
                            <option value={24}>24</option>
                            <option value={30}>30</option>
                            <option value={36}>36</option>
                            <option value={48}>48</option>
                        </select>
                        <span className='text-sm text-gray-400'>per page</span>
                    </div>
                </div>
                {!data.versionRequired ? (
                    <Button
                        disabled={!minecraftVersion}
                        className='px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-150'
                        onClick={handleInstallClick}
                    >
                        Install {type.charAt(0).toUpperCase() + type.slice(1)} {minecraftVersion || ''}
                    </Button>
                ) : (
                    minecraftVersion && (
                        <Button
                            disabled={!selectedBuild}
                            className='px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-150'
                            onClick={handleInstallClick}
                        >
                            Install Build {selectedBuild || ''}
                        </Button>
                    )
                )}
            </div>
            <InstallConfirmationDialog
                open={showConfirmDialog}
                onClose={handleCancelInstall}
                onConfirm={handleConfirmInstall}
                type={type.charAt(0).toUpperCase() + type.slice(1)}
                version={minecraftVersion || ''}
                build={selectedBuild || undefined}
                isLoading={isInstalling}
            />
        </div>
    );
}
