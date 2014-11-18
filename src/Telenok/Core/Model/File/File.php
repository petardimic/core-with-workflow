<?php

namespace Telenok\Core\Model\File;

class File extends \Telenok\Core\Interfaces\Eloquent\Object\Model {

	protected $table = 'file';
	protected $ruleList = ['title' => ['required', 'min:1']];

	public function isImage()
	{
		return $this->exists && in_array($this->uploadFileFileMimeType->mime_type, [
			'image/gif',
			'image/jpeg',
			'image/pjpeg',
			'image/png',
			'image/tiff',
		]);
	}

    public function category()
    {
        return $this->belongsToMany('\Telenok\File\FileCategory', 'pivot_relation_m2m_category_file', 'category_file', 'category')->withTimestamps();
    }

    public function uploadFileFileExtension()
    {
        return $this->belongsTo('\Telenok\File\FileExtension', 'upload_file_file_extension');
    }

    public function uploadFileFileMimeType()
    {
        return $this->belongsTo('\Telenok\File\FileMimeType', 'upload_file_file_mime_type');
    }

}

?>