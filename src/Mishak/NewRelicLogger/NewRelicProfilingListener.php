<?php

namespace Mishak\NewRelicLogger;

use Kdyby;
use Nette;
use Nette\Application\Application;
use Nette\Application\Request;
use Nette\Diagnostics\Debugger;


class NewRelicProfilingListener implements Kdyby\Events\Subscriber
{
	use \Nette\SmartObject;

	public function getSubscribedEvents()
	{
		return array(
			'Nette\\Application\\Application::onStartup',
			'Nette\\Application\\Application::onRequest',
			'Nette\\Application\\Application::onError',
		);
	}


	public function onStartup(Application $app)
	{
		if (!extension_loaded('newrelic')) {
			return;
		}

		$oldLogger = Debugger::getLogger();
        $logger = new Logger(\Tracy\Debugger::$logDirectory);
		$logger->emailSnooze = $oldLogger->emailSnooze;
		$logger->mailer = $oldLogger->mailer;
		$logger->directory = $oldLogger->directory;
		$logger->email = $oldLogger->email;
		Debugger::setLogger($logger);
	}


	public function onRequest(Application $app, Request $request)
	{
		if (!extension_loaded('newrelic')) {
			return;
		}

		if (PHP_SAPI === 'cli') {
			newrelic_name_transaction('$ ' . basename($_SERVER['argv'][0]) . ' ' . implode(' ', array_slice($_SERVER['argv'], 1)));

			newrelic_background_job(TRUE);

			return;
		}

		$params = $request->getParameters();
		newrelic_name_transaction($request->getPresenterName() . (isset($params['action']) ? ':' . $params['action'] : ''));
	}


	public function onError(Application $app, \Throwable $e)
	{
		if (!extension_loaded('newrelic')) {
			return;
		}

		if ($e instanceof Nette\Application\BadRequestException) {
			return;
		}

		newrelic_notice_error($e->getMessage(), $e);
	}

}
