<?php

declare(strict_types=1);
/**
 * Copyright 2019-2022 Seata.io Group.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */
namespace Hyperf\Seata\Core\Rpc\Runtime\Swoole;

use Hyperf\Coroutine\Coroutine;
use Hyperf\Engine\Channel;
use Hyperf\Seata\Core\Protocol\MessageType;
use Hyperf\Seata\Core\Protocol\RpcMessage;
use Hyperf\Seata\Core\Rpc\Address;
use Hyperf\Seata\Core\Rpc\Runtime\ProcessorManager;
use Hyperf\Seata\Core\Rpc\Runtime\SocketChannelInterface;
use Hyperf\Seata\Core\Rpc\Runtime\V1\ProtocolV1Decoder;
use Hyperf\Seata\Core\Rpc\Runtime\V1\ProtocolV1Encoder;
use Hyperf\Seata\Utils\Buffer\ByteBuffer;
use Hyperf\Context\ApplicationContext;
use Hyperf\Seata\Utils\Protocol\RpcMessageUtils;
use Swoole\Coroutine\Socket;

class SocketChannel implements SocketChannelInterface
{
    protected ProtocolV1Encoder $protocolEncoder;

    protected ProtocolV1Decoder $protocolDecoder;

    protected int $messageId;

    protected Socket $socket;

    protected Address $address;

    protected array $responses = [];

    protected Channel $sendChannel;

    protected array $processTable = [];

    public function __construct(Socket $socket, Address $address)
    {
        $this->socket = $socket;
        $this->address = $address;

        $container = ApplicationContext::getContainer();
        $this->protocolEncoder = $container->get(ProtocolV1Encoder::class);
        $this->protocolDecoder = $container->get(ProtocolV1Decoder::class);
        $this->sendChannel = new Channel();
        $this->createRecvLoop();
        $this->createSendLoop();
    }

    public function sendSyncWithResponse(RpcMessage $rpcMessage, int $timeoutMillis)
    {
        $channel = new Channel();
        var_dump('send the rpc message to TC rpcMessage ===> ' . RpcMessageUtils::toLogString($rpcMessage));
        $this->responses[$rpcMessage->getId()] = $channel;
        $this->sendSyncWithoutResponse($rpcMessage, $timeoutMillis);
        return $channel->pop();
    }

    public function sendSyncWithoutResponse(RpcMessage $rpcMessage, int $timeoutMillis): void
    {
        $data = $this->protocolEncoder->encode($rpcMessage);
        $this->socket->send($data);
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    protected function createRecvLoop()
    {
        Coroutine::create(function () {
            $processorManger = ApplicationContext::getContainer()->get(ProcessorManager::class);
            while (true) {
                try {
                    $data = $this->socket->recvAll();
                    if (! $data) {
                        // Coroutines give up
                        usleep(1);
                        continue;
                    }
                    $byteBuffer = ByteBuffer::wrapBinary($data);
                    $rpcMessage = $this->protocolDecoder->decode($byteBuffer);
                    var_dump('Recieved a rpc message ===> ' . RpcMessageUtils::toLogString($rpcMessage) . PHP_EOL);
                    $processorManger->dispatch($this, $rpcMessage);

                    if (isset($this->responses[$rpcMessage->getId()])) {
                        $responseChannel = $this->responses[$rpcMessage->getId()];
                        $responseChannel->push($rpcMessage);
                    }
//                        var_dump('else', $rpcMessage);

//                    elseif ($rpcMessage->getMessageType() === MessageType::TYPE_HEARTBEAT_MSG) {
//                        var_dump('heartbeat', $rpcMessage);
//                    }
                } catch (\InvalidArgumentException $exception) {
                    var_dump('Recieved a rpc message fail error:' . $exception->getMessage() . PHP_EOL);
//                    break;
                } catch (\Throwable $exception) {
                    var_dump('Recieved a rpc message fail error:' . $exception->getMessage() . PHP_EOL);
//                    break;
                }
//                finally {
//                    isset($rpcMessage) && isset($this->responses[$rpcMessage->getId()]) && $this->responses[$rpcMessage->getId()]->close();
//                }
            }
        });
    }

    protected function createSendLoop()
    {
        Coroutine::create(function () {
            while (true) {
                try {
                    $rpcMessage = $this->sendChannel->pop();
                    $data = $this->protocolEncoder->encode($rpcMessage);
                    $this->socket->sendAll($data);
                } catch (\Exception $exception) {
//                    var_dump($exception->getMessage());
                }
            }
        });
    }
}
