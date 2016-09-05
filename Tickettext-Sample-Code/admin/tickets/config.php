<?php
	if ($this->active)
	{ 
		$n = (isset($_GET['n']) && is_numeric($_GET['n']) ? $_GET['n'] : 20);
		$o = (isset($_GET['o']) && is_numeric($_GET['o']) ? $_GET['o'] : 0);

		$b = $this->block('content')->title('Tickets');

			$v = $b->view('ticket', 'table');
				$v->query->in(array('active', 'cancelled'), 'status')->sort('date_paid', true)->limit($n)->offset($o);
				$v->import('date_paid', 'ref')->url($this->url . '{{ref}}/');
                $v->fields['date_paid']->title('Date');
				$v->column('<strong>{{event_name}}</strong><br /><span>{{quantity}}x {{tier_name}}</span>', 'Ticket Title');
				$v->column('{{first}} {{last}}', 'Name');
                $v->column('{{utm_source}}', 'UTM');
				$v->column('<span>{{status}}</span>', 'Status', 'status-{{status}}');
				$v->run();
			$bb = $b->block()->class('submit');
				$bb->button()->text('Download opt-ins')->subtle()->url($this->url . 'marketing/');

				if ($o > 0) $bb->button()->url($this->url . '?n=' . $n . '&o=' . ($o - $n < 0 ? 0 : $o - $n))->text('&larr;')->subtle();
				if (count($v->rows) >= $n) $bb->button()->url($this->url . '?n=' . $n . '&o=' . ($o + $n))->text('&rarr;')->subtle();
	}
	elseif ($this->proud)
	{
		if ($next = $this->next) if ($i = $next->_name) if (is_numeric($i)) if ($ticket = $this->ticket($i, 'ref'))
		{
			$formatted = format_ticket($ticket->ref);
			$pp = $this->page($next)->title('Ticket ' . $formatted);
			if ($pp->active)
			{
				$b = $pp->block('content')->title('Ticket ' . $formatted);

					if (isset($_GET['saved'])) $b->action()->success('Ticket saved');
					$event = $this->event($ticket->event);

					$v = $b->block()->title('Ticket information <a href="' . $pp->url . 'edit/" class="button-header xxsmall right">Edit ticket</a>')->view('ticket', 'definition');

						$v->query->is($ticket->id);
						$v->import('ref');
						$v->fields['ref']->title('Ticket ref');
						$v->column('<a href="' . URL .'admin/events/{{event}}/">{{event_name}}</a>', 'Event name');
						$v->column(date(DATE, $event->date_from + $event->time_from) . ' ' . date(TIME, $event->date_from + $event->time_from), 'Event date');
						$v->column('{{tier_name}}', 'Ticket type');
						$v->column($ticket->quantity . ' (' . $ticket->scanned . ')', 'Quantity (scans)');
						$v->column('<span>{{status}}</span>', 'Ticket status', 'status-{{status}}');
						$v->column('<a href="' . URL . 'ticket/{{ref}}/" target="_blank">' . DOMAIN . '/ticket/{{ref}}</a>', 'Ticket link');

					$v = $b->block()->title('Payment and price')->view('ticket', 'definition');
						$v->query->is($ticket->id);
						if ($ticket->ticket_each)
						{
							$v->column(format_currency($ticket->ticket_each, 'GBP') . ($ticket->fee_each ? ' + ' . format_currency($ticket->fee_each, 'GBP') . ' BF' : ''), 'Price (each)');
							$v->column(format_currency($ticket->ticket_total, 'GBP') . ($ticket->fee_total ? ' + ' . format_currency($ticket->fee_total, 'GBP') . ' BF' : ''), 'Price (total)');
							$v->column(format_currency($ticket->pay, 'GBP'), 'Paid by customer');
						}
						else $v->column('Free', 'Paid by customer');
						if ($ticket->promo)
						{
							$promo = $this->promo($ticket->promo);
							$v->column(format_currency($ticket->promo_total, 'GBP') . ' (<a href="' . URL . 'admin/promo/' . $promo->id . '/">' . $promo->code . '</a> - ' . ($promo->amount_percentage ? $promo->amount_percentage . '%' : format_currency($promo->amount_fixed, 'GBP')) . ' off)', 'Paid by promo code');
						}

					$v = $b->block()->title('Customer information')->view('ticket', 'definition');
						$v->query->is($ticket->id);
						if ($ticket->customer)
						{
							$customer = $this->customer($ticket->customer);
							$v->column('<a href="' . URL . '">' . $customer->first . ' ' . $customer->last . '</a>', 'Customer name');
						}
						$v->column('{{first}} {{last}}', 'Name on ticket');
						$v->column('{{email}}', 'Email address');
						$v->column('{{phone}}', 'Phone number');
						$v->column('{{street}}<br>{{locality}}<br>{{city}}<br>{{postcode}}', 'Address');
			}
			elseif ($pp->proud)
			{
				$ppp = $pp->page('edit')->title('Ticket ' . $formatted . ' - Edit');
				if ($ppp->active)
				{
					$v = $ppp->block('content')->title('Ticket ' . $formatted . ' - edit')->view('ticket', 'edit')->id($ticket->id)->import('status', 'prefix', 'first', 'last', 'email', 'phone', 'street', 'locality', 'city', 'postcode', 'country')->redirectable($pp->url . '?saved');
						$v->fields['prefix']->title('Name on ticket');
						$v->fields['email']->title('Ticket email address');
						$v->fields['phone']->title('Ticket phone number');
						$v->fields['street']->title('Ticket address');

						$v->form->class('form-not-left');
						$v->button('back')->text('Back')->url($pp->url)->subtle();
				}
			}
		}

		$pp = $this->page('marketing');
		if ($pp->active)
		{
			$v = $pp->block('content')->view('ticket', 'export')->import('prefix', 'first', 'last', 'email', 'phone', 'street', 'locality', 'city', 'postcode', 'country', 'marketing');
				$v->fields['prefix']->title('Title');
				$v->fields['first']->title('First name');
				$v->fields['last']->title('Last name');
				$v->fields['email']->title('Email');
				$v->fields['phone']->title('Phone');
				$v->fields['street']->title('Address 1');
				$v->fields['locality']->title('Address 2');
				$v->fields['city']->title('City');
				$v->fields['postcode']->title('Postcode');
				$v->fields['country']->title('Country');
				$v->fields['marketing']->title('Marketing');
				$v->query->is(true, 'marketing');
		}
	}
?>