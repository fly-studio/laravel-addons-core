<?php
namespace Addons\Core\Models;

use Addons\Core\Models\DownloadThread;
class DownloadManager{

	private $x64 = false;
	private $url;
	private $filename;
	private $threadCountLimit = 5; //同时下载線程數
	private $fp; //文件指針
	private $buffer; //buffer指針
	private $bufferOffsets = []; //历史写入数组
	private $bufferSize = 32 * 1024 * 1024; //写入缓存，32M
	private $threads = []; //線程數組
	private $size = 0; //文件總大小
	private $minPartialSize = 1024 * 1024; //分塊下載最小1M,
	private $initSize = 128 * 1024; //最初下載128K

	public function __construct($url, $filename = NULL)
	{
		$this->x64 = intval(2147483649) > 0;
		$this->url = $url;
		!empty($filename) && $this->setFilename($filename);
	}

	public function __destruct()
	{
		//$this->bufferToFile();
		fclose($this->fp);
	}

	public function download()
	{
		$thread = new DownloadThread($this, 0, $this->initSize); //先读取128K，检查是否能续传

		if ($thread->download())
		{
			$curl = $thread->getcUrl();
			$length = intval($curl->responseHeaders['Content-Length']);

			if (isset($curl->responseHeaders['Content-Range'])) //能续传
			{
				list(, $start, $total) = sscanf($curl->responseHeaders['Content-Range'], 'bytes %d-%d/%d');
				$this->size = $total; //总数
				if (++$start < $total) //没下载完
				{
					$remainderSize = $total - $start; //剩余size
					$averageSize = ceil($remainderSize / $this->threadCountLimit);
					$averageSize < $this->minPartialSize && $averageSize = $this->minPartialSize; //确保每个分块 > minPartialSize

					while (true)
					{
						$length = $start + $averageSize > $total ? $total - $start : $averageSize;

						$this->threads[] = $thread = new DownloadThread($this, $start, $length);
						$thread->download();

						$start += $length;
						if ($start >= $total) break;
					}
				} else { //已经下载完
					$this->onComplete();
				}

			} else { //不能续传(无法支持RANGE头)，故而该thread会一直下载完毕，而不会启动新的线程
				$this->size = $length;
				$this->onComplete();
			}
		}
	}

	public function onPartialComplete(DownloadThread $thread)
	{
		//线程接受回寫文件
		$this->bufferToFile();
	}

	public function onComplete()
	{

	}

	private function createBuff()
	{
		if (is_resource($this->buffer)) return FALSE;
		$this->buffer = fopen('php://memory','wb+');
		rewind($this->buffer);
		return TRUE;
	}

	public function writeToBuffer($content, $offset, $length)
	{
		$this->createBuff();
		//写入互斥信号
		flock($this->buffer, LOCK_EX); //独占
		if (!isset($this->bufferOffsets[$offset]) || $length > $this->bufferOffsets[$offset]) //没有该offset,或者现在提交的长度比之前的长，则计入缓存
		{
			$s = pack($this->x64 ? 'QL' : 'LL', $offset, $length);

			fwrite($this->buffer, pack($this->x64 ? 'QL' : 'LL', $offset, $length));
			fwrite($this->buffer, $content, $length);
			$this->bufferOffsets[$key] = $length;
		}
		//解除互斥信号
		flock($this->buffer, LOCK_UN); //释放
		if (ftell($this->buffer) >= $this->bufferSize) //超過了buffer的大小，則回寫文件，并重新創建Buffer
			$this->bufferToFile();		
	}

	private function bufferToFile()
	{
		if (empty($this->buffer) || !is_resource($this->buffer)) return false;

		flock($this->buffer, LOCK_EX); //独占
		flock($this->fp, LOCK_EX); //独占

		$size = ftell($this->buffer);
		//if (empty($size)) return false;

		rewind($this->buffer);
		while (ftell($this->buffer) < $size)
		{
			$str = fread($this->buffer, $this->x64 ? 8 + 4 : 8);
			$tmp = unpack($this->x64 ?  'Qoffset/Llength' : 'Loffset/Llength', $str);
			extract($tmp);

			if (!empty($length))
			{
				
				fseek($this->fp, $offset);
				fwrite($this->fp, fread($this->buffer, $length));
			} else
				break;
		}
		rewind($this->buffer); //指针归位，避免再次写入
		flock($this->fp, LOCK_UN); //释放
		flock($this->buffer, LOCK_UN); //释放

		$this->destroyBuff();
		return true;
	}

	private function destroyBuff()
	{
		fclose($this->buffer);
		$this->buffer = null;
	}

	public function getSize()
	{
		return $this->size;
	}

	public function getThreadCount()
	{
		return count($this->threads);
	}

	public function getDownloadedSize()
	{
		/*计算缺失的数
		0  5              0  1  2  3  4
		2  10                   2  3  4  5  6  7  8  9
		                                               10 11
		10 7                                           10 11 12 13 14 15 16
		20 5                                                                              20 21 22 23 24

		2-0-5 = -3 重合3个
		10-2-10 = -2 重合2个
		20-10-7 = 3 缺3个
		*/
		$offsets = $this->bufferOffsets;
		ksort($offsets);$offsets[$this->getSize() - 1] = 0; //将总数加到结尾
		$total = 0;
		while(list($offset, $length) = each($offsets)) //注意：最后一个会重复计算一次
		{
			//下一个
			$nextOffset = key($offsets);
			$diff = $nextOffset - $offset - $length;
			$total += $diff > 0 ? $diff : 0; //正数表示缺
		}

		return $this->getSize() - $total;

	}

	public function setThreadCountLimit($limit)
	{
		$this->threadCountLimit = $limit;
	}

	public function getUrl()
	{
		return $this->url;
	}

	public function getFilename()
	{
		return $this->filename;
	}

	public function setFilename($filename)
	{
		is_resource($this->fp) && fclose($this->fp);

		$this->filename = $filename;
		$this->fp = fopen($filename, 'wb+');
	}

}