<?php

namespace Addons\Core\Contracts;

interface Protobufable {
	public function toProtobuf(): \Google\Protobuf\Internal\Message;
}
