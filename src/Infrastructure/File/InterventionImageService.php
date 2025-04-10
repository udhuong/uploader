<?php

namespace Udhuong\Uploader\Infrastructure\File;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Interfaces\ImageInterface;
use Udhuong\Uploader\Domain\Entity\Media;
use Udhuong\Uploader\Infrastructure\Factories\MediaFactory;
use Udhuong\Uploader\Presentation\Facades\InterventionImage;

class InterventionImageService
{
    private Media $media;

    private ImageInterface $image;

    public function make(Media $media): self
    {
        $this->media = $media;
        $this->image = InterventionImage::read($media->absolutePath);

        return $this;
    }

    /**
     * Tạo ảnh thumbnail
     */
    public function thumbnail(?int $width = 50, ?int $height = 50): Media
    {
        $this->image->scaleDown($width, $height);
        $paths = explode('/', $this->media->path);
        $paths[count($paths) - 1] = $this->media->nameNoExtension.'_thumbnail.'.$this->media->extension;
        $this->media->path = implode('/', $paths);

        Storage::disk($this->media->disk)->put($this->media->path, (string) $this->image->encode());

        return MediaFactory::fromUploaded($this->media->path, $this->media->disk);
    }

    /**
     * Giảm kích thước ảnh khi vượt quá kích thước cho phép
     */
    public function resizeWidth(int $width = 800): Media
    {
        $this->image->resize($width);
        $paths = explode('/', $this->media->path);
        $paths[count($paths) - 1] = $this->media->nameNoExtension.'_resize.'.$this->media->extension;
        $this->media->path = implode('/', $paths);

        Storage::disk($this->media->disk)->put($this->media->path, (string) $this->image->encode());

        return MediaFactory::fromUploaded($this->media->path, $this->media->disk);
    }

    /**
     * Watermark ảnh
     */
    public function watermark(string $path): Media
    {
        $watermark = InterventionImage::read($path);

        // Kích thước ảnh gốc
        $imageWidth = $this->image->width();
        $imageHeight = $this->image->height();

        // Kích thước watermark
        $watermarkWidth = $watermark->width();
        $watermarkHeight = $watermark->height();

        // Phủ watermark toàn bộ ảnh
        for ($x = 0; $x < $imageWidth; $x += $watermarkWidth) {
            for ($y = 0; $y < $imageHeight; $y += $watermarkHeight) {
                $this->image->place($watermark, 'top-left', $x, $y);
            }
        }

        $paths = explode('/', $this->media->path);
        $paths[count($paths) - 1] = $this->media->nameNoExtension.'_watermark.'.$this->media->extension;
        $this->media->path = implode('/', $paths);

        Storage::disk($this->media->disk)->put($this->media->path, (string) $this->image->encode());

        return MediaFactory::fromUploaded($this->media->path, $this->media->disk);
    }
}
