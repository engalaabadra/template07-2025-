<?php

namespace App\Models\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\Traits\Relations\MediaRelationsTrait;
use App\Enums\ServiceResponseEnum;

/**
 * Trait HasMediaTrait
 *
 * Provides common functionality for uploading, storing, retrieving, and deleting media files 
 * (e.g., images and files) associated with a model.
 * 
 * This trait assumes you have defined appropriate media relationships 
 * via MediaRelationsTrait (e.g., image(), images(), file(), files()).
 */
trait HasMediaTrait
{
    /**
     * Store a single uploaded media file in the specified folder.
     *
     * - Generates a unique filename by appending the current timestamp.
     * - Stores file under "uploads/{folder}" path in the `public` disk.
     *
     * @param  UploadedFile $file   The uploaded file.
     * @param  string       $type   Type of media (e.g., 'image', 'file').
     * @param  string       $folder Destination folder name.
     * @return string               The full path to the stored file.
     */
    protected function storeMediaFileInFolder(UploadedFile $file, string $type, string $folder): string
    {
        $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $filenameToStore = "{$filename}_" . time() . ".{$extension}";

        return $file->storeAs("uploads/{$folder}", $filenameToStore, 'public');
    }

    /**
     * Upload and attach a single media file to the model.
     *
     * - Supports both 'image' and 'file' types.
     * - Automatically updates the existing related media if exists; otherwise creates a new one.
     *
     * @param  UploadedFile $file   The uploaded file.
     * @param  string       $type   Type of media ('image' or 'file').
     * @param  string       $folder Folder name to store the media.
     * @return string               Publicly accessible URL of the uploaded file.
     */
    public function uploadSingleMedia(UploadedFile $file, string $type, string $folder): string
    {
        $path = $this->storeMediaFileInFolder($file, $type, $folder);
        $url = str_replace('public/', 'storage/', $path);

        $data = [
            'url' => $url,
            'type' => $type,
        ];

        if ($type === 'image') {
            $this->image()->exists() ? $this->image()->update($data) : $this->image()->create($data);
        } elseif ($type === 'file') {
            $this->file()->exists() ? $this->file()->update($data) : $this->file()->create($data);
        }

        return $url;
    }

    /**
     * Upload and attach multiple media files to the model.
     *
     * - Stores all files under the given folder.
     * - Creates many records in the related media table.
     *
     * @param  array  $files  Array of UploadedFile objects.
     * @param  string $type   Type of media ('image' or 'file').
     * @param  string $folder Target folder name.
     * @return array          Array of uploaded items with URLs and types.
     */
    public function uploadMultipleMedia(array $files, string $type, string $folder): array
    {
        $uploaded = [];
        foreach ($files as $file) {
            $path = $this->storeMediaFileInFolder($file, $type, $folder);
            $url = str_replace('public/', 'storage/', $path);
            $uploaded[] = ['url' => $url, 'type' => $type];
        }

        if ($type === 'image') {
            $this->images()->createMany($uploaded);
        } elseif ($type === 'file') {
            $this->files()->createMany($uploaded);
        }

        return $uploaded;
    }

    /**
     * Delete media records by their IDs from the specified relation.
     *
     * - Supports deletion from any relation name (e.g., 'images', 'files').
     * - Also deletes physical files from the disk.
     * - Returns `NOT_FOUND` enum if any of the IDs do not belong to the relation.
     *
     * @param  array|string  $ids       Array or string("all") of media IDs to delete. Can include comma-separated string as a single item.
     * @param  string $relation  The media relation name on the model (e.g., 'files', 'images').
     * @return int|\App\Enums\ServiceResponseEnum
     *
     * @throws \InvalidArgumentException If the relation method does not exist.
     */
    public function deleteMediaByIds($ids, string $relation)
{
    if (! method_exists($this, $relation)) {
        throw new \InvalidArgumentException("Relation [$relation] does not exist on model " . static::class);
    }

    // Case 1: if the input is "all" → delete everything in the relation
    if ($ids === 'all' || (is_array($ids) && count($ids) === 1 && $ids[0] === 'all')) {
        return $this->$relation()->delete();
    }

    // Case 2: if the input is an array with a single comma-separated string → split it into an array
    if (is_array($ids) && count($ids) === 1 && is_string($ids[0]) && str_contains($ids[0], ',')) {
        $ids = explode(',', $ids[0]);
    }

    // Case 3: if the input is a single string (not "all") → wrap it into an array
    if (is_string($ids) && $ids !== 'all') {
        $ids = [$ids];
    }

    // At this point, $ids should be an array of IDs → delete only those
    return $this->$relation()->whereIn('id', $ids)->delete();
}


    /**
     * Delete a single related media item (e.g., `image` or `file`).
     *
     * - Automatically deletes the file from storage if it exists.
     * - Deletes the database record.
     *
     * @param  string $relation  The name of the relation method (e.g., 'image', 'file').
     * @return void
     */
    public function deleteSingleMedia(string $relation): void
    {
        $item = $this->{$relation};
        if ($item) {
            $path = str_replace(url('storage') . '/', '', $item->url);
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
            $item->delete();
        }
    }
}
