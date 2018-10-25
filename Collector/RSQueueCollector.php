<?php

/**
 * RSQueueBundle for Symfony2
 *
 * Marc Morera 2013
 */

namespace Mmoreram\RSQueueBundle\Collector;

use Mmoreram\RSQueueBundle\Event\RSQueueProducerEvent;
use Mmoreram\RSQueueBundle\Event\RSQueuePublisherEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * Collector for RSQueue data.
 *
 * All these methods are subscribed to custom RSQueueBundle events
 */
class RSQueueCollector extends DataCollector
{
    private $total;

    /**
     * Construct method for initializate all data.
     *
     * Also initializes total value to 0
     */
    public function __construct()
    {
        $this->reset();
    }

    /**
     * Subscribed to RSQueueProducer event.
     *
     * Add to collect data a new producer action
     *
     * @param RSQueueProducerEvent $event Event fired
     *
     * @return RSQueueCollector self Object
     */
    public function onProducerAction(RSQueueProducerEvent $event)
    {
        ++$this->data['total'];
        $this->data['prod'][] = [
            'payload' => $event->getPayloadSerialized(),
            'queue' => $event->getQueueName(),
        ];

        return $this;
    }

    /**
     * Subscribed to RSQueuePublisher event.
     *
     * Add to collect data a new publisher action
     *
     * @param RSQueuePublisherEvent $event Event fired
     *
     * @return RSQueueCollector self Object
     */
    public function onPublisherAction(RSQueuePublisherEvent $event)
    {
        ++$this->data['total'];
        $this->data['publ'][] = [
            'payload' => $event->getPayloadSerialized(),
            'queue' => $event->getChannelName(),
        ];

        return $this;
    }

    /**
     * Get total of queue interactions.
     *
     * @return int
     */
    public function getTotal()
    {
        return (int) $this->data['total'];
    }

    /**
     * Get producer collection.
     *
     * @return array
     */
    public function getProducer()
    {
        return $this->data['prod'];
    }

    /**
     * Get publisher collection.
     *
     * @return array
     */
    public function getPublisher()
    {
        return $this->data['publ'];
    }

    /**
     * Collects data for the given Request and Response.
     *
     * @param Request    $request
     * @param Response   $response
     * @param \Exception $exception
     *
     * @api
     */
    public function collect(
        Request $request,
        Response $response,
        \Exception $exception = null
    ) {
    }

    /**
     * Reset collector.
     */
    public function reset()
    {
        $this->total = 0;
        $this->data = [
            'prod' => [],
            'publ' => [],
            'total' => 0,
        ];
    }

    /**
     * Return collector name.
     *
     * @return string Collector name
     */
    public function getName()
    {
        return 'rsqueue_collector';
    }
}
