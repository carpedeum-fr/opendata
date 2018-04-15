<?php

namespace App\Command;


use App\Entity\Diocese;
use App\Entity\Parish;
use App\Entity\Place;
use App\Entity\Time;
use Doctrine\Common\Persistence\ObjectManager;
use Geocoder\ProviderAggregator;
use GuzzleHttp\Client;
use libphonenumber\PhoneNumberUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

abstract class ImportCommand extends ContainerAwareCommand
{
    private $client;
    protected $em;
    protected $dioceseRepository;
    protected $parishRepository;
    protected $churchRepository;
    protected $timeRepository;
    protected $provider;
    protected $stopwatch;
    protected $timers = [];
    protected $phoneUtil;

    const BASE_MESSE_INFO_URL = 'https://www.messes.info/';
    const MESSE_INFO_QUERY_PARAM = ['userkey'=>'test', 'format'=>'json'];
    const GET_DIOCESE = 'api/v2/dioceses/';
    const GET_PARISH = 'api/v2/diocese/';
    const GET_CHURCH = 'api/v2/lieux-par-communaute/';
    const GET_TIME = 'api/v2/horaires_par_lieu/';

    public function __construct(
        ObjectManager $manager,
        ProviderAggregator $provider,
        Stopwatch $stopwatch)
    {
        $this->client= new Client();
        $this->em = $manager;
        $this->dioceseRepository = $this->em->getRepository(Diocese::class);
        $this->parishRepository = $this->em->getRepository(Parish::class);
        $this->churchRepository = $this->em->getRepository(Place::class);
        $this->timeRepository = $this->em->getRepository(Time::class);
        $this->provider = $provider;
        $this->stopwatch = $stopwatch;
        $this->phoneUtil = PhoneNumberUtil::getInstance();

        // this is required due to parent constructor, which sets up name
        parent::__construct();
    }

    protected function dumpRecap(SymfonyStyle $io)
    {
        $event = $this->stopwatch->stop('global');
        $io->note('Total duration: '.$event->getDuration().'ms');


        $cleanTimers = [];
        /** @var Stopwatch $timer */
        foreach ($this->timers as $key => $timer) {
            $cleanTimers[] = [$key, $timer->getDuration().'ms'];
        }
        $io->table(
            array('Object', 'Duration'),
            $cleanTimers
        );
    }

    protected function getJson($url)
    {
        $url = self::BASE_MESSE_INFO_URL . $url . '?' .http_build_query(self::MESSE_INFO_QUERY_PARAM);
        $response = $this->client->request('GET', $url);
        //$jsonFile = file_get_contents('');

        return json_decode($response->getBody(), true);
    }

    protected function getDiocese()
    {
        return $this->getJson(self::GET_DIOCESE);
    }

    protected function getParish($diocese)
    {
        return $this->getJson(self::GET_PARISH . $diocese);
    }

    protected function getChurch($parishCode)
    {
        return $this->getJson(self::GET_CHURCH . $parishCode);
    }

    protected function getTime($churchId)
    {
        return $this->getJson(self::GET_TIME . $churchId);
    }
}