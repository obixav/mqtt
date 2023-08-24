<?php

namespace App\Console\Commands;

use App\Models\CommandStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;
use Symfony\Component\Console\Command\Command as CommandAlias;

class TestMqtt extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-mqtt {type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    public $server   = 'broker.emqx.io';
    public $port     = 1883;
    public $clientId = "mqttx_4fe83179";
    public $username = 'emqx_user';
    public $password = 'public';
    public $clean_session = false;
    public $mqtt_version = MqttClient::MQTT_3_1_1;
    /**
     * Execute the console command.
     */
    public function handle()
    {
        if($this->argument('type')=='publish')
        {
            $this->publish();
        }else{
            $this->subscribe();
        }

    }

    public function subscribe()
    {
        $server   = $this->server;
        $port     = $this->port;
        $clientId =$this->clientId;
        $username = $this->username;
        $password = $this->password;
        $clean_session = $this->clean_session;
        $mqtt_version =$this->mqtt_version;

        $connectionSettings  = (new ConnectionSettings)
            ->setUsername($username)
            ->setPassword($password)
            ->setKeepAliveInterval(60)
            ->setMaxReconnectAttempts(10)
            // Last Will 设置
            ->setLastWillTopic('command/door/last-will')
            ->setLastWillMessage('client disconnect')
            ->setLastWillQualityOfService(1);


        $mqtt = new MqttClient($server, $port, $clientId, $mqtt_version);

        $mqtt->connect($connectionSettings, $clean_session);
        printf("client connected\n");

        $mqtt->subscribe('command/door', function ($topic, $message) {
            printf("Received message on topic [%s]: %s\n", $topic, $message);
            $message2=json_decode($message,true);
            CommandStatus::firstOrCreate([ 'published_at'=>$message2["time"]],['topic'=>$topic,'message'=>$message,
                'published_at'=>$message2["time"],'command'=>$message2["command"]]);
        }, 0);
        $mqtt->loop(true,true);
        return CommandAlias::SUCCESS;
    }

    public function publish()
    {
        $server   = $this->server;
        $port     = $this->port;
        $clientId =$this->clientId;
        $username = $this->username;
        $password = $this->password;
        $clean_session = $this->clean_session;
        $mqtt_version =$this->mqtt_version;

        $connectionSettings  = (new ConnectionSettings)
            ->setUsername($username)
            ->setPassword($password)
            ->setKeepAliveInterval(60)
            // Last Will 设置
            ->setLastWillTopic('command/door/last-will')
            ->setLastWillMessage('client disconnect')
            ->setLastWillQualityOfService(1);
        $mqtt = new MqttClient($server, $port, $clientId, $mqtt_version);
        $mqtt->connect($connectionSettings, $clean_session);
        printf("client connected\n");
            $payload = array(
                'command' => 'OpenDoor',
                'time'=>Carbon::now()
            );
            $mqtt->publish(
            // topic
                'command/door',
                // payload
                json_encode($payload),
                // qos
                0,
                // retain
                true
            );
            printf("msg sent");
            sleep(1);
        $mqtt->loop(true);
        return CommandAlias::SUCCESS;
    }
}
