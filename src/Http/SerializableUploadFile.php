<?php
namespace Addons\Core\Http;

//use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

class SerializableUploadFile implements \Serializable {

	private $file;

	public function __construct(SymfonyUploadedFile $file = null)
	{
		$this->file = $file;
	}

	/**
	 * Serializes the file
	 *
	 * @return string|null
	 * @link http://php.net/manual/en/serializable.serialize.php
	 */
	public function serialize()
	{
		return serialize($this->data());
	}

	/**
	 * Unserializes the file.
	 *
	 *
	 * @param string $serialized
	 *
	 * @throws Exception
	 * @link http://php.net/manual/en/serializable.unserialize.php
	 */
	public function unserialize($serialized)
	{
		$data = unserialize($serialized);
		return !empty($data) ? $this->invoke($data) : null;
	}

	public function data()
	{
		try {
			return [
				'path' => $this->file->getPathname(),
				'originalName' => $this->file->getClientOriginalName(),
				'mimeType' => $this->file->getClientMimeType() ?: $this->file->getMimeType(),
				'size' => $this->file->getSize(),
				'error' => $this->file->getError(),
			];
		} catch (\Exception $e) {
			return [];
		}
	}

	public function invoke($data)
	{
		//UploadedFile::createFromBase()
		return new SymfonyUploadedFile($data['path'], $data['originalName'], $data['mimeType'], $data['size'], $data['error']);
	}
}
