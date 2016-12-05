<?php

/*
 * This file is part of the FtvenConsoleBundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ftven\ConsoleBundle\Form\Handler;

use Ftven\ConsoleBundle\Form\Model\ConsoleApplication;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class BroadcastHandler.
 *
 * @author Michael COULLERET <michael.coulleret@francetv.com>
 * @author Amine Fattouch <amine.fattouch@francetv.fr>
 */
class ConsoleApplicationFormHandler
{
    /**
     * @var FormInterface
     */
    private $form;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * Constructor class.
     *
     * @param FormInterface   $form
     * @param KernelInterface $kernel
     */
    public function __construct(FormInterface $form, KernelInterface $kernel)
    {
        $this->form   = $form;
        $this->kernel = $kernel;
    }

    /**
     * process.
     *
     * @param Request            $request
     * @param ConsoleApplication $consoleApplication
     *
     * @return boolean
     */
    public function process(Request $request, ConsoleApplication $consoleApplication)
    {
        $content = [];

        $parameters = json_decode($request->getContent(), true);
        if ($parameters == null) {
            throw new BadRequestHttpException('Json Malformed !');
        }

        $request->request = new ParameterBag($parameters);

        $this->form->setData($consoleApplication);
        $this->form->handleRequest($request);

        if ($this->form->isSubmitted() && $this->form->isValid()) {
            $application = new Application($this->kernel);
            $application->setAutoExit(false);

            foreach ($consoleApplication->getCommands() as $command) {
                $commandDefinition = ['command' => $command->getDefinition()];
                foreach ($command->getArguments() as $argument) {
                    $commandDefinition[$argument->getName()] = $argument->getValue();
                }

                $input  = new ArrayInput($commandDefinition);
                $output = new BufferedOutput();

                $application->run($input, $output);

                $content[] = $output->fetch();
            }
        }

        return $content;
    }
}
