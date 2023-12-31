<?php

namespace Jatdung\MediaManager\Adapters;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Carbon;
use Jatdung\MediaManager\Exceptions\AdapterException;
use Jatdung\MediaManager\Exceptions\DriverException;
use Jatdung\MediaManager\MediaManagerServiceProvider;

class Adapter
{
    /**
     * @var bool[]
     */
    protected $directorySupport = [
        'makeDirectory' => true,
        'move' => true,
    ];

    /**
     * @param FilesystemAdapter $disk
     */
    public function __construct(protected FilesystemAdapter $disk)
    {
        $this->setUpDisk($disk);
    }

    /**
     * @param FilesystemAdapter $disk
     * @return $this
     */
    protected function setUpDisk(FilesystemAdapter $disk)
    {
        $this->disk = $disk;

        return $this;
    }

    /**
     * List Files
     *
     * @param string $path
     * @param bool $deep
     * @return \League\Flysystem\DirectoryListing
     *
     * @throws AdapterException
     * @throws \League\Flysystem\FilesystemException
     */
    public function list(string $path, bool $deep = false)
    {
        if (!$this->directoryExists($path)) {
            throw new AdapterException(sprintf('Path [%s] not exists', $path));
        }

        return $this->disk()->listContents($path, $deep);
    }

    /**
     * Move
     *
     * @param string $source
     * @param string $destination
     * @return bool
     */
    public function move(string $source, string $destination)
    {
        if (!$this->supportMoveDirectory() && $this->directoryExists($source)) {
            throw new DriverException(sprintf(
                'this disk [%s] do not support move directories.',
                get_class($this->disk())
            ));
        }

        return $this->disk()->move($source, $destination);
    }

    /**
     * Rename
     *
     * @param string $source
     * @param string $destination
     * @return bool
     */
    public function rename(string $source, string $destination)
    {
        return $this->move($source, $destination);
    }

    /**
     * Delete
     *
     * @param $paths
     * @return bool
     */
    public function delete($paths)
    {
        return $this->disk()->delete($paths);
    }

    /**
     * Delete directory
     *
     * @param string $path
     * @return bool
     */
    public function deleteDirecotry(string $path)
    {
        return $this->disk()->deleteDirectory($path);
    }

    /**
     * @param string $name
     * @return bool
     *
     * @throws DriverException
     */
    public function makeDirectory(string $name)
    {
        if (!$this->supportMakeDirectory()) {
            throw new DriverException(sprintf(
                'this disk [%s] do not support make directories.',
                get_class($this->disk())
            ));
        }

        return $this->disk()->makeDirectory($name);
    }

    /**
     * @param string $path
     * @return string
     */
    public function url(string $path)
    {
        return $this->disk()->url($path);
    }

    public function metadata(string $path)
    {
        return $this->buildMetaDataFromArray([
            'name' => basename($path),
            'path' => $path,
            'url' => $this->url($path),
            'mimeType' => $this->disk()->mimeType($path),
            'fileSize' => format_byte($this->disk()->fileSize($path), 2),
            'lastModifiedAt' => Carbon::createFromTimestamp($this->disk()->lastModified($path)) ?: '',
        ]);
    }

    protected function buildMetaDataFromArray(array $data)
    {
        return [
            'name' => ['label' => trans('admin.name'), 'value' => $data['name']],
            'path' => [
                'label' => MediaManagerServiceProvider::trans('media.path'),
                'value' => $data['path']
            ],
            'url' => [
                'label' => MediaManagerServiceProvider::trans('media.url'),
                'value' => $data['url']
            ],
            'mimeType' => [
                'label' => 'mime type',
                'value' => $data['mimeType']
            ],
            'fileSize' => [
                'label' => trans('admin.size'),
                'value' => $data['fileSize']
            ],
            'lastModifiedAt' => [
                'label' => trans('admin.updated_at'),
                'value' => $data['lastModifiedAt']
            ]
        ];
    }

    /**
     * @param string $path
     * @return bool
     *
     * @throws \League\Flysystem\FilesystemException
     */
    public function exists(string $path)
    {
        return $this->disk()->has($path);
    }

    /**
     * @param string $path
     * @return bool
     */
    public function directoryExists(string $path)
    {
        if ($path === '' || $path === '/') {
            return true;
        }
        return $this->disk()->directoryExists($path);
    }

    /**
     * @param string $path
     * @return string
     */
    public function imageThumbnail(string $path)
    {
        return $this->url($path);
    }

    /**
     * @return FilesystemAdapter
     */
    public function disk()
    {
        return $this->disk;
    }

    /**
     * @return bool
     */
    public function supportMakeDirectory(): bool
    {
        return (bool)$this->directorySupport['makeDirectory'];
    }

    /**
     * @return bool
     */
    public function supportMoveDirectory(): bool
    {
        return (bool)$this->directorySupport['move'];
    }

    /**
     * @param ...$args
     * @return static
     */
    public static function make(...$args)
    {
        return new static(...$args);
    }
}
