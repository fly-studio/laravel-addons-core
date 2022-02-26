<?php

namespace Addons\Core\Http\Response;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\BinaryFileResponse as BaseBinaryFileResponse;

class BinaryFileResponse extends BaseBinaryFileResponse {

	/**
	 * Automatically sets the ETag header according to the checksum of the file.
	 */
	public function setAutoEtag(): static
	{
		$this->setEtag(md5(serialize(fileinfo($this->file->getPathname()))));
		return $this;
	}


	/**
	 * Sets the file to stream.
	 *
	 * @param \SplFileInfo|string $file               The file to stream
	 * @param string              $contentDisposition
	 * @param bool                $autoEtag
	 * @param bool                $autoLastModified
	 *
	 * @return BinaryFileResponse
	 *
	 * @throws FileException
	 */
	public function setFile(\SplFileInfo|string $file, string $contentDisposition = null, bool|string $autoEtag = false, bool|string|int $autoLastModified = true): static
	{
		if (!$file instanceof File) {
			if ($file instanceof \SplFileInfo) {
				$file = new File($file->getPathname());
			} else {
				$file = new File((string) $file);
			}
		}

		if (!$file->isReadable()) {
			throw new FileException('File must be readable.');
		}

		$this->file = $file;

		if ($autoEtag === true)
			$this->setAutoEtag();
		elseif (!empty($autoEtag))
			$this->setEtag($autoEtag);

		if ($autoLastModified === true)
			$this->setAutoLastModified();
		elseif (!empty($lastModified)) {
			if (is_numeric($lastModified))
				$autoLastModified = '@'.$autoLastModified;
			$this->setLastModified(new \DateTime($autoLastModified));
		}

		if ($contentDisposition)
			$this->setContentDisposition($contentDisposition);


		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function prepare(Request $request): static
	{

		parent::prepare($request);
		$lastModified = $this->getLastModified();

		if (!empty($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) == $this->getEtag())
		{
			$this->setStatusCode(304);
			//此时必须退出，不然因为etag等头会触发chorme pending的BUG
			abort(304, '', ['Last-Modified' => $lastModified instanceof \DateTime ? $lastModified->format('D, d M Y H:i:s').' GMT' : $lastModified]);
			$this->maxlen = 0;
		}
		else if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']))
		{
			if (strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= ($lastModified instanceof \DateTime ? $lastModified->getTimeStamp() : $lastModified))
			{
				$this->setStatusCode(304);
				abort(304, '' , ['Last-Modified' => $lastModified instanceof \DateTime ? $lastModified->format('D, d M Y H:i:s').' GMT' : $lastModified]);
				$this->maxlen = 0;
			}
		}
		else
		{
			if (isset($_SERVER['SERVER_SOFTWARE']))
			{
				switch (substr($_SERVER['SERVER_SOFTWARE'], 0, (int)strpos($_SERVER['SERVER_SOFTWARE'],'/'))) {
					case 'nginx':
						$path = str_replace(['/./', '//'], ['/', '/'], rtrim(config('session.path'), '\\/').'/'.relative_path($this->file->getPathname(), public_path()));
						$this->headers->set('X-Accel-Redirect', $path);
						$this->headers->set('X-Accel-Buffering', 'no');
						//$this->headers->set('X-Accel-Limit-Rate', '102400'); //速度限制 Byte/s
						//$this->headers('Accept-Ranges', 'none');//单线程 限制多线程
						$this->maxlen = 0;
						break;
					case 'Apache':
						if (function_exists('apache_get_modules') && in_array('mod_xsendfile', apache_get_modules()))
						{
							$this->headers->set('X-Sendfile', $this->file->getPathname());
							$this->maxlen = 0;
						}
						break;
					case 'squid':
						$this->headers->set('X-Accelerator-Vary', $this->file->getPathname());
						$this->maxlen = 0;
						break;
					case 'lighttpd':
						$this->headers->set('X-LIGHTTPD-send-file', $this->file->getPathname());
						$this->maxlen = 0;
						break;
				}
			}


		}
		return $this;
	}
}
