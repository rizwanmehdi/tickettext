<?php 
    global $site;
    global $ticket;
    $slug = null;

    $clone_page = clone $this;
    if($clone_page->proud)
    {
        $slug = $this->next->_name;
        $ticket = $this->ticket($slug, 'ref');
    }

    if((IS_LIVE || IS_STAGING || IS_LOCAL) && !IS_WHITE_LABEL && $slug)
    {
        $event = $site->event($ticket->event);
        $wl = $site->whitelabel($event->promoter, 'promoter');

        if($wl && $event->ballot_type == 1)
        {
            redirect('https://' . $wl->subdomain .'.' . DOMAIN_PARTIAL . REQUEST_URL);
        }
    }


    if ($this->active) redirect(URL . 'customer');
    elseif ($this->proud && $slug = $this->next->_name)
    {
        sleep(1);
        $site->page->class('layout-ticket');

        if (valid_ticket($slug))
        {
            if ($ticket = $this->ticket($slug, 'ref'))
            {

                if(!function_exists('e_tracker_code'))
                {
                    // Hook for adding to html <head>
                    function e_tracker_code(page $page)
                    {
                        global $site;
                        global $ticket;
                        $script = false;

                        if(isset($_GET['ordered']))
                        {
                            $event = $site->query('event')->is($ticket->event, 'id')->row();
                            $t_promoter = $site->query('promoter')->is($event->promoter, 'id')->row();
                          
                            if($script)
                            {
                                $value = null;
                                $value = format_currency($ticket->pay,'GBP');
                                $value = str_replace('&pound;', '', $value);
                                $script = str_replace('{{value}}', $value, $script);
                                echo $script;
                            }
                        }
                    }

                    hook_for('page', 'html5_head', 'e_tracker_code');
                }

                //Creating and download pdf of ticket
                $ppp = $this->page($slug);
                $p = $this->page($slug)->title('Ticket');
                $event = $site->event($ticket->event);
               
			   if ($p->active)
                {
                    $p->block('banner')->class('banner-small');
                    $b = $p->block('content');
                        if (isset($_GET['ordered'])) {

                            $site->page->class('ordered-success');
                            $b->block()->callback($this->action()->success('Thanks for your purchase!'));

                            if (!IS_WHITE_LABEL) $b->block('overlay')->block()->tag('div')->class('overlay')->data('ticket', $ticket)->file('overlay.php');

                        }
                        elseif(IS_WHITE_LABEL && isset($_GET['ballot-entry']) && defined('IS_HAT_TRICK_PRODUCTIONS') && IS_HAT_TRICK_PRODUCTIONS)
                        {
                            $site->page->class('ordered-success');
                            // add the back link to the success
                            $b->block()->tag('span')->class('ticketsuccess-back-link')->text('<a href="http://hat-trick-productions.tickettext.co.uk">Back to Hat Trick Productions</a>');
                            $b->block()->callback($this->action()->success('Thanks for applying to join the audience of:'));

                        }
                        elseif(IS_WHITE_LABEL && defined('IS_HAT_TRICK_PRODUCTIONS') && IS_HAT_TRICK_PRODUCTIONS)
                        {
                            $site->page->class('ordered-success');
                            // add the back link to the success
                            $b->block()->tag('span')->class('ticketsuccess-back-link')->text('<a href="https://hat-trick-productions.tickettext.co.uk">Back to Hat Trick Productions</a>');
                            $b->block()->callback($this->action()->success('Thanks for applying to join the audience of:'));
                        }
                        elseif ($ticket->status == 'active')
                        {
                            // Message about the email.
                            if ($ticket->email && $ticket->date_delivered_email)
                            {
                                $b->block()->callback($a_email = $this->action());
                                if (isset($_GET['remail']))
                                {
                                    $ticket->deliver_email()->update();
                                    $a_email->success('Ticket was delivered to <strong>' . $ticket->email . '</strong> on <strong>' . date('d/m/Y', $ticket->date_delivered_email) . '</strong>');
                                }
                                else $a_email->info('Ticket was delivered to <strong>' . $ticket->email . '</strong> on <strong>' . date('d/m/Y', $ticket->date_delivered_email) . '</strong> <a href="?remail" class="button xsmall button-subtle">Resend</a>');
                            }

                            // Message about the SMS.
                            if ($ticket->phone && $ticket->date_delivered_sms)
                            {
                                $b->block()->callback($a_sms = $this->action());
                                if (isset($_GET['resms']))
                                {
                                    $ticket->deliver_sms()->update();
                                    $a_sms->success('Ticket was delivered to <strong>' . $ticket->phone . '</strong> on <strong>' . date('d/m/Y', $ticket->date_delivered_sms) . '</strong>');
                                }
                                else $a_sms->info('Ticket was delivered to <strong>' . $ticket->phone . '</strong> on <strong>' . date('d/m/Y', $ticket->date_delivered_sms) . '</strong> <a href="?resms" class="button xsmall button-subtle">Resend</a>');
                            }
                        }

                        $b->block('ticket-wrap')->callback($ticket, 'e_ticket');


                }
                else
                {
                    if ($p->page('qr')->active) $ticket->e_qr();
                }
                return true;
            }
            else sleep(5);
        }
        else sleep(5);
    }
?>