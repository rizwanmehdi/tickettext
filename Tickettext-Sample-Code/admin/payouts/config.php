<?php

// Shortcut vars.

$site = $this->_site;
$page = $site->page; // Reference to the 'active' page.
// This page.

if ($this->active) {
    $b = $this->block('content')->title('Payouts due');
    if (isset($_GET['in']))
        $b->action()->success('Welcome back!');
    $v = $b->block()->view('event', 'table')->import('date_from')->url('{{id}}/');
    $v->column('{{promoter_name}}', 'Promoter');
    $v->column('<strong>{{name}}</strong> <br>at {{venue_name}} in {{location_name}}', 'Event');
    $v->fields['date_from']->title('Event date');
    $v->query->is(null, 'payout_date')->more(0, 'out')->less(time(), 'date_to')->sort('date_from', true);
    $v->run();
    if (!$v->rows)
        $v->action->info('No payouts');
    $s = $b->block()->class('submit');
    $s->button()->url($this->url . 'done/')->text('Completed payouts')->subtle();
}
elseif ($this->proud) {
    $p_done = $this->page('done')->title('Payouts');
    if ($p_done->active) {
        $n = (isset($_GET['n']) && is_numeric($_GET['n']) ? $_GET['n'] : 100);
        $o = (isset($_GET['o']) && is_numeric($_GET['o']) ? $_GET['o'] : 0);
        $b = $p_done->block('content')->title('Completed payouts');
        $v = $b->block()->view('event', 'extended_table')->import('payout_date', 'date_from')->url('{{id}}/');
        $v->query->not(NULL, 'payout_date')->sort('payout_date', true)->limit($n)->offset($o);
        $v->column('{{promoter_name}}', 'Promoter');
        $v->column('<strong>{{name}}</strong> <br>at {{venue_name}} in {{location_name}}', 'Event');
        $v->fields['date_from']->title('Date');
        $v->column_extended('{{ticket_total}}', 'Net face value', 'gross-face-v', 'ticket');
        $v->column_extended('{{ticket_total}}', 'Gross face value', 'gross-face-v', 'ticket');
        $v->column_extended('{{fee_total}}', 'Booking fee total', 'booking-fee-total', 'ticket');
        $v->run();

        if (!$v->rows)
            $v->action->info('No payouts');
        $s = $b->block()->class('submit');
        $s->button('back')->url($this->url)->text('Back')->subtle();
        if ($o > 0)
            $s->button()->url($p_done->url . '?n=' . $n . '&o=' . ($o - $n < 0 ? 0 : $o - $n))->text('&larr;')->subtle();
        if (count($v->rows) >= $n)
            $s->button()->url($p_done->url . '?n=' . $n . '&o=' . ($o + $n))->text('&rarr;')->subtle();
    }
    elseif ($next = $this->next)
        if ($name = $next->_name)
            if (is_numeric($name))
                if ($event = $this->event($name)) {
                    $p = $this->page($next)->title($event->name . ' - Payout');
                    if ($p->active) {
                        $promoter = $this->promoter($event->promoter);
                        $b = $p->block('content')->title($event->name . ' - payout');

                        if (!$event->payout_date) {
                            $total = 0;
                            $total_promo = 0;
                            $absorbTotal = 0;
                            $q = $this->query('ticket')->is('active', 'status')->is($event->id, 'event')->more(0, 'out');
                            while ($r = $q->row()){
                                $total += $r->out;
                                $absorbTotal += $r->absorb_total;
                            }
                            $q = $this->query('ticket')->is('active', 'status')->is($event->id, 'event');
                            while ($r = $q->row())
                                $total_promo += $r->promo_total;
                        }

                        if (isset($_GET['payout'])) {
                            $event->datas(array(
                                'payout_date' => time(),
                                'payout_amount' => ($total - $total_promo)-$absorbTotal,
                                //'payout_amount' => $total ,
                                'payout_name' => $promoter->bank_name,
                                'payout_sort' => $promoter->bank_sort,
                                'payout_account' => $promoter->bank_account,
                            ));

                            if ($event->update()) {
                                // Send email.
                                $promoter->email(
                                        'Ticket Text - Payout made', 'A payout has been made to your bank account from Ticket Text.' . EOL .
                                        EOL .
                                        'Event details' . EOL .
                                        EOL .
                                        'Event name: ' . $event->name . EOL .
                                        'Event date: ' . date(DATE, $event->date_from) . EOL .
                                        'Ticket sales: ' . $event->sold . EOL .
                                        'Payout sub-total: ' . format_currency($total, 'GBP') . EOL .
                                        'Discount total: ' . format_currency($total_promo, 'GBP') . EOL .
                                        'Absorbed booking fees: ' . format_currency($absorbTotal, 'GBP') . EOL .
                                        
                                        'Payout amount: ' . format_currency(($total - $total_promo)-$absorbTotal, 'GBP') . EOL .
                                        EOL .
                                        'Bank details' . EOL .
                                        EOL .
                                        'Account name: ' . $event->payout_name . EOL .
                                        'Sort code: ' . $event->payout_sort . EOL .
                                        'Sort account: ' . ($event->payout_account ? str_repeat('*', strlen($event->payout_account) - 4) . substr($event->payout_account, -4) : '') . EOL .
                                        EOL .
                                        'To see full details of this payout please see:' . EOL .
                                        URL . 'promoter/payouts/' . $event->id . '/'
                                );

                                $b->action()->success('Payout recorded - promoter has been notified');
                            }
                        }

                        $v = $b->block()->title('Payout details <a href="edit/" class="button-header xxsmall right">Edit payout</a>')->view('event', 'definition');
                        $site->script(URL.'/assets/js/payout-fix.js');
                        if (isset($_GET['saved']))
                            $v->action->success('Payout saved');
                        $v->query->is($event->id);
                        $v->import('date_from', 'name', 'sold');                        
                        if ($event->payout_date) { 
                            $eventPayoutAmount = 0;
                            $q = $this->query('event')->is('active', 'status')->is($event->id, 'id');
                            while ($r = $q->row())
                                $eventPayoutAmount = $r->payout_amount;
                            $v->column(format_currency($eventPayoutAmount, 'GBP'), 'Event payout amount');
                            
                            $total = 0;
                            $absorbTotal = 0;
                            $q = $this->query('ticket')->is('active', 'status')->is($event->id, 'event')->more(0, 'out');
                            while ($r = $q->row()){
                                $total += $r->out;
                                $ticketTotal += $r->ticket_total;
                                $absorbTotal += $r->absorb_total;
                                $payTotal += $r->pay;
                                $feeTotal += $r->fee_total;
                            }
                            //$v->column(format_currency($total, 'GBP'), 'Payout sub-total');
                            $v->column(format_currency($ticketTotal, 'GBP'), 'Payout sub-total');
                            $total_promo = 0;
                            $q = $this->query('ticket')->is('active', 'status')->is($event->id, 'event');
                            while ($r = $q->row())
                                $total_promo += $r->promo_total;
                            $v->column(format_currency($total_promo, 'GBP'), 'Discount total');
                            $v->column(format_currency($absorbTotal, 'GBP'), 'Absorbed booking fees');                            
                            $v->column('<b>'.format_currency(($total - $total_promo)-$absorbTotal, 'GBP') . '<p>&nbsp;</p></b>', '<b>Payout total</b>');
                            
                            $v->column(format_currency($ticketTotal, 'GBP'), 'Ticket column total');
                            
                            $v->column(format_currency($payTotal, 'GBP'), 'Pay column total');
                            
                            $v->column(format_currency($feeTotal, 'GBP'), 'Fee column total');
                            
                            $v->column('<span>Complete</span>', 'Payout status', 'status-active');
                            $v->column(date('d/m/Y', $event->payout_date), 'Payout date', 'status-active');
                            $v->column($event->payout_name . '<br>' . $event->payout_sort . '<br>' . $event->payout_account, 'Paid out to');
                        } else { 
                            $total = 0;
                            $ticketTotal = 0;
                            $absorbTotal = 0;
                            $payTotal = 0;
                            $feeTotal = 0;
                            $q = $this->query('ticket')->is('active', 'status')->is($event->id, 'event')->more(0, 'out');                            
                            while ($r = $q->row()){
                                $total += $r->out;
                                $ticketTotal += $r->ticket_total;
                                $absorbTotal += $r->absorb_total;
                                $payTotal += $r->pay;
                                $feeTotal += $r->fee_total;
                            }
                            
                            $v->column(format_currency($ticketTotal, 'GBP'), 'Payout sub-total');
                            
                            $total_promo = 0;
                            $q = $this->query('ticket')->is('active', 'status')->is($event->id, 'event');
                            while ($r = $q->row())
                                $total_promo += $r->promo_total;
                            $v->column(format_currency($total_promo, 'GBP'), 'Discount total'); 
                            $v->column(format_currency($absorbTotal, 'GBP'), 'Absorbed booking fees');
                            $v->column('<b>'.format_currency(($total - $total_promo)-$absorbTotal, 'GBP') . '<p>&nbsp;</p></b>', '<b>Payout total</b>');
                                
                            $v->column(format_currency($ticketTotal, 'GBP'), 'Ticket column total');
                            
                            $v->column(format_currency($payTotal, 'GBP'), 'Pay column total');
                            
                            $v->column(format_currency($feeTotal, 'GBP'), 'Fee column total');
                            
                            $v->column('<span>Not complete</span>', 'Payout status', 'status-cancelled');

                            if ($total)
                                $b->block()->class('center')->tag('p')->button()->class('large')->text('Mark as paid out')->url('?payout');
                        }


                        
                        $v = $b->block()->title('Promoter details')->titled()->view('promoter', 'definition');
                        $v->query->is($event->promoter);
                        $v->import('name');
                        $v->column('{{first}} {{last}}<br>{{email}}<br>{{phone}}', 'Contact');
                        if ($promoter->bank_name) {
                            $v->action->info('Incorrect bank details? <a href="?remind" class="button button-subtle">Remind</a>');

                            $v->column('{{bank_name}}', 'Account name');
                            $v->column('{{bank_sort}}', 'Sort code');
                            $v->column('{{bank_account}}', 'Account number');
                        } elseif (isset($_GET['remind'])) {
                            // Send email.
                            $promoter->email(
                                    'Ticket Text - Cannot make payout', 'We are attempting to make a payout for your event, but you have provided incomplete or incorrect bank transfer details.' . EOL .
                                    EOL .
                                    'To update your bank account details please visit the following page:' . EOL .
                                    URL . 'promoter/banking/' . EOL .
                                    EOL .
                                    'Event details' . EOL .
                                    EOL .
                                    'Event name: ' . $event->name . EOL .
                                    'Event date: ' . date('d/m/Y', $event->date_from) . EOL .
                                    'Ticket sales: ' . $event->sold . EOL .
                                    'Payout amount: ' . format_currency($total, 'GBP')
                            );
                            $v->action->success('Reminder email sent');
                        } else {
                            $v->action->error('No bank details! <a href="?remind" class="button button-subtle">Remind</a>');
                        }

                        $v = $b->block()->title('Payout breakdown')->view('ticket', 'table');
                        $v->query->in(array('active', 'cancelled'), 'status')->is($event->id, 'event')->sort('first')->sort('last');
                        $v->import('ref', 'out', 'promo_total')->url(URL . 'admin/tickets/{{ref}}/');
                        $v->fields['out']->title('Payout');
                        $v->column('{{first}} {{last}}', 'Name');
                        $v->column('{{quantity}} x {{tier_name}}', 'Ticket Title');
                        $v->column('<span>{{status}}</span>', 'Status', 'status-{{status}}');
                        $v->fields['promo_total']->title('Discount (-)');
                        $v->run();
                        if (!$v->rows)
                            $v->action->info('No ticket sales');
                    }
                    elseif ($p->proud) {
                        $p_edit = $p->page('edit')->title($event->name . ' - Edit');
                        if ($p_edit->active) {
                            $v = $p_edit->block('content')->title($event->name . ' - edit payout')->view('event', 'edit')->id($event->id)->import('payout_date', 'payout_amount')->redirectable($p->url . '?saved');
                            $v->form->class('form-not-left');
                            $v->button->text('Save payout');
                            $v->button('back')->url($p->url)->text('Back')->subtle();
                            $v->run();
                        }
                    }
                }
}
