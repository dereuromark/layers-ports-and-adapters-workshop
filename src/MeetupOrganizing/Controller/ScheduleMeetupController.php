<?php
declare(strict_types=1);

namespace MeetupOrganizing\Controller;

use InvalidArgumentException;
use MeetupOrganizing\Entity\ScheduledDate;
use MeetupOrganizing\MeetupService;
use MeetupOrganizing\ScheduleMeetup;
use MeetupOrganizing\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Expressive\Router\RouterInterface;
use Zend\Expressive\Template\TemplateRendererInterface;

final class ScheduleMeetupController
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var TemplateRendererInterface
     */
    private $renderer;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var MeetupService
     */
    private $meetupService;

    public function __construct(
        Session $session,
        TemplateRendererInterface $renderer,
        RouterInterface $router,
        MeetupService $meetupService
    ) {
        $this->session = $session;
        $this->renderer = $renderer;
        $this->router = $router;
        $this->meetupService = $meetupService;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    ): ResponseInterface {
        $formErrors = [];
        $formData = [
            // This is a nice place to set some defaults
            'scheduleForTime' => '20:00'
        ];

        if ($request->getMethod() === 'POST') {
            $formData = $request->getParsedBody();

            if (empty($formData['name'])) {
                $formErrors['name'][] = 'Provide a name';
            }
            if (empty($formData['description'])) {
                $formErrors['description'][] = 'Provide a description';
            }
            if (empty($formData['scheduleForDate'])) {
                $formErrors['scheduleForDate'][] = 'Provide a date';
            }
            if (empty($formData['scheduleForTime'])) {
                $formErrors['scheduleForTime'][] = 'Provide a time';
            }
            try {
                ScheduledDate::fromPhpDateString(
                    $formData['scheduleForDate'] . ' ' . $formData['scheduleForTime']
                );
            } catch (InvalidArgumentException $exception) {
                $formErrors['scheduleForDate'][] = 'Invalid date/time';
                $formErrors['scheduleForTime'][] = 'Invalid date/time';
            }

            if (empty($formErrors)) {
                $meetupId = $this->meetupService->scheduleMeetup(
                    new ScheduleMeetup(
                        $this->session->getLoggedInUser()->userId()->asInt(),
                        $formData['name'],
                        $formData['description'],
                        $formData['scheduleForDate'] . ' ' . $formData['scheduleForTime']
                    )
                );

                $this->session->addSuccessFlash('Your meetup was scheduled successfully');

                return new RedirectResponse(
                    $this->router->generateUri(
                        'meetup_details',
                        [
                            'id' => $meetupId
                        ]
                    )
                );
            }
        }

        $response->getBody()->write(
            $this->renderer->render(
                'schedule-meetup.html.twig',
                [
                    'formData' => $formData,
                    'formErrors' => $formErrors
                ]
            )
        );

        return $response;
    }
}
