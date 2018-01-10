<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Demo\EmailNotifications\Command;

use Symfony\Component\Validator\Constraints as Assert;
use Desperado\Domain\Message\AbstractCommand;

/**
 * Send email
 *
 * @see EmailSentEvent
 * @see EmailSentFailedEvent
 */
class SendEmailCommand extends AbstractCommand
{
    /**
     * Operation identifier
     *
     * @Assert\NotBlank(
     *     message="operation identifier must be specified"
     * )
     *
     * @var string
     */
    protected $requestId;

    /**
     * From address
     *
     * @Assert\NotBlank(
     *     message="source email hash must be specified"
     * )
     * @Assert\Email(
     *     message="source email is incorrect"
     * )
     *
     * @var string
     */
    protected $fromEmail;

    /**
     * To address
     *
     * @Assert\NotBlank(
     *     message="destination email hash must be specified"
     * )
     * @Assert\Email(
     *     message="destination email is incorrect"
     * )
     *
     * @var string
     */
    protected $toEmail;

    /**
     * Message body
     *
     * @Assert\NotBlank(
     *     message="message body must be specified"
     * )
     * @Assert\Length(
     *      min = 50,
     *      max = 30000,
     *      minMessage = "message body must be at least {{ limit }} characters long",
     *      maxMessage = "message body cannot be longer than {{ limit }} characters"
     * )
     *
     * @var string
     */
    protected $body;

    /**
     * Message body
     *
     * @Assert\NotBlank(
     *     message="message subject must be specified"
     * )
     * @Assert\Length(
     *      min = 5,
     *      max = 30,
     *      minMessage = "message subject must be at least {{ limit }} characters long",
     *      maxMessage = "message subject cannot be longer than {{ limit }} characters"
     * )
     *
     * @var string
     */
    protected $subject;

    /**
     * Get operation id
     *
     * @return string
     */
    public function getRequestId(): string
    {
        return $this->requestId;
    }

    /**
     * Get source email
     *
     * @return string
     */
    public function getFromEmail(): string
    {
        return $this->fromEmail;
    }

    /**
     * Get destination email
     *
     * @return string
     */
    public function getToEmail(): string
    {
        return $this->toEmail;
    }

    /**
     * Get message body
     *
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Get message subject
     *
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }
}
