<?php

namespace FekiWebstudio\PreviewMail;

use Illuminate\Mail\TransportManager;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class PreviewMailServiceProvider extends ServiceProvider
{
    /**
     * This will register a closure which will be run when 'swift.transport'
     * (the transport manager) is first resolved.
     * Then we extend the transport manager, by adding the preview transport
     * object as the 'preview' driver.
     */
    public function register()
    {
        $this->app->extend(
            'swift.transport',
            function (TransportManager $manager) {
                $manager->extend('preview', function () {
                    $recipients = $this
                        ->app['config']
                        ->get('mail.preview_to', []);

                    if (empty($recipients)) {
                        throw new InvalidArgumentException("Please set the e-mail recipients for preview.");
                    }

                    return new PreviewMailTransport($recipients);
                });

                return $manager;
            }
        );
    }
}