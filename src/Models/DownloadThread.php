<?php
namespace Addons\Core\Models;


use \Curl\Curl;
use Addons\Core\Models\DownloadManager;
class DownloadThread {

	private $curl;
	private $buffer;
	private $offset;
	private $length;
	private $lastDownloadSize = 0;
	private $completed = false;

	public function __construct(DownloadManager $manager, $offset = 0, $length = 0)
	{
		$this->manager = $manager;
		$this->offset = $offset;
		$this->length = $length;

		$this->buffer = fopen('php://temp', 'wb+');//tmpfile();

		$this->curl = new Curl();
		if (!empty($length))
			$this->curl->setOpt(CURLOPT_RANGE, $offset.'-'.($offset + $length - 1));
		$this->curl->setOpt(CURLOPT_FILE, $this->buffer);
		$this->curl->setOpt(CURLOPT_BINARYTRANSFER, TRUE);
		$this->curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
		$this->curl->setOpt(CURLOPT_SSL_VERIFYHOST, false);
		$this->curl->setOpt(CURLOPT_NOPROGRESS, false);
		$this->curl->setOpt(CURLOPT_PROGRESSFUNCTION, array($this, 'onProgressing'));
		$this->curl->setOpt(CURLOPT_BUFFERSIZE, 64*1024); //64K
		$this->curl->success(array($this, 'onComplete'));
		$this->curl->error(array($this, 'onError'));
	}

	public function download()
	{
		$this->curl->get($this->manager->getUrl());
		fclose($this->buffer);
		return !$this->curl->error;
	}

	public function onError()
	{

	}

	public function onProgressing($resource, $download_size = 0, $downloaded = 0, $upload_size = 0, $uploaded = 0)
	{
		if (version_compare(PHP_VERSION, '5.5.0') < 0)
		{
			$uploaded = $upload_size;
			$upload_size = $downloaded;
			$downloaded = $download_size;
			$download_size = $resource;
		}

		if (($size = $downloaded - $this->lastDownloadSize) > 0)
		{
			//将数据发送到buffer
			$this->writeToBuffer($this->lastDownloadSize, $size);
		}
		$this->lastDownloadSize = $downloaded;

	}

	public function writeToBuffer($offset, $size)
	{
		if (empty($size))
			return false;
		rewind($this->buffer); //指针归为
		$content = fread($this->buffer, $size);
		rewind($this->buffer); //指针归为
		$this->manager->writeToBuffer($content, $this->offset + $offset, $size);
	}

	public function onComplete()
	{
		$this->completed = true;
		$this->manager->onPartialComplete($this);
	}

	public function getcURL()
	{
		return $this->curl;
	}

	public function isCompleted()
	{
		return $this->completed;
	}
	
}