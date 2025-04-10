<?php

namespace Udhuong\Uploader\Presentation\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Udhuong\LaravelCommon\Presentation\Http\Controllers\Controller;
use Udhuong\LaravelCommon\Presentation\Http\Response\Responder;
use Udhuong\Uploader\Domain\Actions\SaveMediaAction;
use Udhuong\Uploader\Infrastructure\Factories\MediaFactory;
use Udhuong\Uploader\Infrastructure\File\InterventionImageService;
use Udhuong\Uploader\Presentation\Facades\Upload;
use Udhuong\Uploader\Presentation\Http\Requests\UploadRequest;
use Udhuong\Uploader\Presentation\Http\Response\UploadResponse;

class UploadController extends Controller
{
    public function __construct(
        private readonly SaveMediaAction $saveMediaAction,
        private readonly InterventionImageService $interventionImageService
    ) {}

    /**
     * Upload file cơ bản
     *
     * @throws ConnectionException
     */
    public function upload(UploadRequest $request): JsonResponse
    {
        $files = $request->file('files', []);
        $urls = $request->get('urls', []);
        $uploadedFiles = [];

        $disk = config('uploader.disk');
        $userId = auth()->id() ?? 1;

        $upload = Upload::folderDate()->disk($disk);

        foreach ($files as $file) {
            $path = $upload->uploadFile($file);
            $media = MediaFactory::fromUploaded($path, $disk);
            $media->originalName = $file->getClientOriginalName();
            $media->userId = $userId;
            $this->saveMediaAction->handle($media);
            $uploadedFiles[] = $media;
        }

        foreach ($urls as $url) {
            $path = $upload->uploadSinkFromUrl($url);
            $media = MediaFactory::fromUploaded($path, $disk);
            $media->originalName = pathinfo($url, PATHINFO_BASENAME);
            $media->userId = $userId;
            $this->saveMediaAction->handle($media);
            $uploadedFiles[] = $media;
        }

        foreach ($uploadedFiles as $media) {
            if (str_starts_with($media->mimeType, 'image/')) {
                $this->interventionImageService->make($media)->thumbnail(300, 300);
                $this->interventionImageService->make($media)->resizeWidth(800);
                $this->interventionImageService->make($media)->watermark(public_path('watermark.png'));
            }
        }

        return Responder::success(UploadResponse::format($uploadedFiles), 'Upload thành công');
    }
}
