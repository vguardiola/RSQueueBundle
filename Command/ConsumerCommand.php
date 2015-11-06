<?php

/**
 * RSQueueBundle for Symfony2
 *
 * Marc Morera 2013
 */

namespace Mmoreram\RSQueueBundle\Command;

use Mmoreram\RSQueueBundle\Command\Abstracts\AbstractRSQueueCommand;
use Mmoreram\RSQueueBundle\Exception\ConsumerException;
use Mmoreram\RSQueueBundle\Exception\InvalidAliasException;
use Mmoreram\RSQueueBundle\Exception\MethodNotFoundException;
use Mmoreram\RSQueueBundle\Resolver\QueueAliasResolver;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Abstract consumer command
 *
 * Events :
 *
 *     Each time a consumer recieves a new element, this throws a new
 *     rsqueue.consumer Event
 *
 * Exceptions :
 *
 *     If any of inserted queues or channels is not defined in config file
 *     as an alias, a new InvalidAliasException will be thrown
 *
 *     Likewise, if any ot inserted associated methods does not exist or is not
 *     callable, a new MethodNotFoundException will be thrown
 */
abstract class ConsumerCommand extends AbstractRSQueueCommand
{

    /**
     * Adds a queue to subscribe on
     *
     * Checks if queue assigned method exists and is callable
     *
     * @param String $queueAlias Queue alias
     * @param String $queueMethod Queue method
     *
     * @return SubscriberCommand self Object
     *
     * @throws MethodNotFoundException If any method is not callable
     */
    protected function addQueue($queueAlias, $queueMethod)
    {
        return $this->addMethod($queueAlias, $queueMethod);
    }

    /**
     * Configure command
     *
     * Some options are included
     * * timeout ( default: 0)
     * * iterations ( default: 0)
     * * sleep ( default: 0)
     *
     * Important !!
     *
     * All Commands with this consumer behaviour must call parent() configure method
     */
    protected function configure()
    {
        $this
            ->addOption(
                'timeout',
                null,
                InputOption::VALUE_OPTIONAL,
                'Consumer timeout.
                If 0, no timeout is set.
                Otherwise consumer will lose conection after timeout if queue is empty.
                By default, 0',
                0
            )
            ->addOption(
                'iterations',
                null,
                InputOption::VALUE_OPTIONAL,
                'Number of iterations before this command kills itself.
                If 0, consumer will listen queue until process is killed by hand or by exception.
                You can manage this behavour by using some Process Control System, e.g. Supervisord
                By default, 0',
                0
            )
            ->addOption(
                'sleep',
                null,
                InputOption::VALUE_OPTIONAL,
                'Timeout between each iteration ( in seconds ).
                If 0, no time will be waitted between them.
                Otherwise, php will sleep X seconds each iteration.
                By default, 0',
                0
            )
            ->addOption(
                'gap',
                null,
                InputOption::VALUE_OPTIONAL,
                'Time between retries ( in seconds ).
                By default, 60',
                60
            );
    }

    /**
     * Execute code.
     *
     * Each time new payload is consumed from queue, consume() method is called.
     * When iterations get the limit, process literaly dies
     *
     * @param InputInterface  $input An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return int|null|void
     * @throws InvalidAliasException If any alias is not defined
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->define();

        $consumer = $this->getContainer()->get('rs_queue.consumer');
        $iterations = (int) $input->getOption('iterations');
        $timeout = (int) $input->getOption('timeout');
        $sleep = (int) $input->getOption('sleep');
        $gap = (int) $input->getOption('gap');
        $iterationsDone = 0;
        $queuesAlias = array_keys($this->methods);

        if ($this->shuffleQueues()) {

            shuffle($queuesAlias);
        }

        while ($response = $consumer->consume($queuesAlias, $timeout)) {

            list($queueAlias, $payload) = $response;
            if (array_key_exists('atTime', $payload) && $payload['atTime'] > time()) {
                $producer = $this->getContainer()->get('rs_queue.producer');
                $producer->produce($queueAlias, $payload);
                usleep(500000);
            } else {
                $method = $this->methods[$queueAlias];

                /**
                 * All custom methods must have these parameters
                 *
                 * InputInterface  $input   An InputInterface instance
                 * OutputInterface $output  An OutputInterface instance
                 * Mixed           $payload Payload
                 */
                try {
                    $this->$method($input, $output, $payload);
                } catch (ConsumerException $e) {
                    if ($payload['maxRetries'] > 0) {
                        if (array_key_exists('retries', $payload)) {
                            $payload['retries']++;
                        } else {
                            $payload['retries'] = 1;
                        }
                        $producer = $this->getContainer()->get('rs_queue.producer');
                        if ($payload['retries'] < $payload['maxRetries']) {
                            echo 'lo re-probaremos';
                            $payload['atTime'] = time() + $gap;
                            $producer->produce($queueAlias, $payload);
                        } else {
                            $producer->produce(QueueAliasResolver::ERROR_QUEUE_ALIAS, $payload);
                        }
                    }
                }
            }
            if (($iterations > 0) && (++$iterationsDone >= $iterations)) {
                break;
            }
            if ($this->shuffleQueues()) {
                shuffle($queuesAlias);
            }
            sleep($sleep);
        }
    }
}
