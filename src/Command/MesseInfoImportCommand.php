<?php

namespace App\Command;

use App\Entity\Place;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MesseInfoImportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('import:messeinfo')
            ->setDescription('Import data using MesseInfo API.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $client = new \GuzzleHttp\Client();
        $em = $this->getContainer()->get('doctrine')->getManager();

        $dioceseList = $client->request('GET', 'https://www.messes.info/api/v2/dioceses/?format=json&userkey=test');
        $dioceses = [];

        foreach (json_decode($dioceseList->getBody(), true) as $diocese)
        {
            $dioceses[$diocese['id']] = $diocese['name'];
        }

        foreach ($dioceses as $dioceseId => $dioceseName)
        {
            $output->writeln($dioceseName);
            $paroisseList = $client->request('GET', 'http://www.messes.info/api/v2/diocese/'.$dioceseId.'?userkey=test&format=json');
            foreach (json_decode($paroisseList->getBody(), true) as $paroisse){
                $output->writeln($paroisse['name']);
                if (!array_key_exists('alias', $paroisse)) {
                    $output->writeln('<error>No alias!</error>');
                    continue;
                }
                $egliseList = $client->request('GET', 'http://www.messes.info/api/v2/lieux-par-communaute/'.$paroisse['alias'].'?userkey=test&format=json');
                foreach (json_decode($egliseList->getBody(), true) as $eglise) {
                    $place = new Place();
                    if (array_key_exists('name', $eglise)) {
                        $place->name = $eglise['name'];
                    }
                    if (array_key_exists('city', $eglise)) {
                        $place->addressLocality = $eglise['city'];
                    }
                    if (array_key_exists('address', $eglise)) {
                        $place->streetAddress = $eglise['address'];
                    }
                    if (array_key_exists('zipcode', $eglise)) {
                        $place->postalCode = $eglise['zipcode'];
                    }

                    $em->persist($place);
                }
            }
            $em->flush();
        }
    }
}