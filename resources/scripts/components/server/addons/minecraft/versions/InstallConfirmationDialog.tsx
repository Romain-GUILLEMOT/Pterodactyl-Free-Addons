import React from 'react';
import { Button } from '@/components/elements/button';
import { ExclamationIcon, TrashIcon } from '@heroicons/react/outline';
import Dialog from '@/components/elements/dialog/Dialog';

interface InstallConfirmationDialogProps {
    open: boolean;
    onClose: () => void;
    onConfirm: (deleteFiles: boolean) => void;
    type: string;
    version: string;
    build?: string;
    isLoading?: boolean;
}

export default function InstallConfirmationDialog({
    open,
    onClose,
    onConfirm,
    type,
    version,
    build,
    isLoading = false,
}: InstallConfirmationDialogProps) {
    const [deleteFiles, setDeleteFiles] = React.useState(false);

    const handleConfirm = () => {
        onConfirm(deleteFiles);
    };

    const versionText = build ? `${type} build ${build} for Minecraft ${version}` : `${type} version ${version}`;

    return (
        <Dialog
            open={open}
            onClose={onClose}
            title='Confirm Installation'
            description='Please review the installation details before proceeding.'
        >
            <div className='mt-4 space-y-4'>
                <div className='p-4 bg-gray-800 rounded-lg border border-gray-700'>
                    <h4 className='text-sm font-medium text-white mb-2'>Installation Details</h4>
                    <p className='text-sm text-gray-300'>
                        You are about to install <span className='font-semibold text-blue-400'>{versionText}</span>.
                    </p>
                    <p className='text-sm text-gray-400 mt-2'>
                        This process will download and configure the selected version on your server.
                    </p>
                </div>

                <div className='p-4 bg-red-900/20 rounded-lg border border-red-700/50'>
                    <div className='flex items-start space-x-3'>
                        <div className='flex-shrink-0'>
                            <input
                                type='checkbox'
                                id='deleteFiles'
                                checked={deleteFiles}
                                onChange={(e) => setDeleteFiles(e.target.checked)}
                                className='w-4 h-4 text-red-600 bg-gray-700 border-gray-600 rounded focus:ring-red-500 focus:ring-2'
                            />
                        </div>
                        <div className='flex-1 min-w-0'>
                            <label htmlFor='deleteFiles' className='flex items-center cursor-pointer'>
                                <TrashIcon className='w-4 h-4 text-red-400 mr-2' />
                                <span className='text-sm font-medium text-red-400'>
                                    Delete all server files before installation
                                </span>
                            </label>
                            <p className='text-xs text-red-300 mt-1'>
                                Warning: This will permanently delete all existing files, including worlds, plugins, and
                                configurations.
                            </p>
                        </div>
                    </div>
                </div>

                <div className='p-3 bg-blue-900/20 rounded border border-blue-700/50'>
                    <p className='text-xs text-blue-300'>
                        <strong>Note:</strong> The installation process may take a few minutes. Your server will be
                        automatically restarted once the installation is complete.
                    </p>
                </div>
            </div>

            <div className='mt-3'>
                <Button.Text onClick={onClose} disabled={isLoading} className='mr-3'>
                    Cancel
                </Button.Text>
                <Button onClick={handleConfirm} disabled={isLoading} className='bg-green-600 hover:bg-green-700'>
                    {isLoading ? (
                        <>
                            <div className='w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2' />
                            Installing...
                        </>
                    ) : (
                        'Start Installation'
                    )}
                </Button>
            </div>
        </Dialog>
    );
}
