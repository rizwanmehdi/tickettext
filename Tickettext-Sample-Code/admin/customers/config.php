<?php
	if ($this->active)
	{
		$b = $this->block('content')->title('Customers');

			if (isset($_GET['added'])) $b->action()->success('Customer added');
			elseif (isset($_GET['saved'])) $b->action()->success('Customer saved');
			elseif (isset($_GET['deleted'])) $b->action()->success('Customer deleted');

			$v = $b->view('customer', 'table')->import('active')->url($this->url . '{{id}}/');
				$v->query->sort('first')->sort('last');
				$v->column('{{first}} {{last}}', 'Name');
			$bb = $b->block()->class('submit');
				$bb->button()->text('Download opt-ins')->subtle()->url($this->url . 'marketing/');
	}
	elseif ($this->proud)
	{
		$i = $this->next->_name;
		if (is_numeric($i)) if ($customer = $this->customer($i))
		{
			$pp = $this->page($i)->title($customer->first . ' ' . $customer->last);
			if ($pp->active)
			{
				$b = $pp->block('content')->title($customer->first . ' ' . $customer->last);
                $date_created = $customer->date_created;

                if(strlen($date_created) > 0){
                    $date_created = date('d/m/Y', $date_created);
                }else{
                    $date_created = 'N/A';
                }


				$v = $b->block()->title('Customer summary <a href="' . $pp->url . 'edit/" class="button-header xxsmall right">Edit customer</a>')->view('customer', 'definition')->import('active');
					$v->column($date_created, 'Date Created');
                    $v->column('{{prefix}} {{first}} {{last}}', 'Customer name');
					$v->column('{{email}}', 'Customer email');
					$v->column('{{phone}}', 'Customer phone');
					$v->column('{{street}}<br>{{locality}}<br>{{city}}<br>{{postcode}}', 'Customer address');
                    if(strlen($customer->dob) > 0){
                        $v->column(date('d/m/Y', strtotime($customer->dob)), 'D.O.B');
                    }

					$v->query->is($i);

				$v = $b->block()->title('Ticket sales')->view('ticket', 'table');
					$v->query->in(array('active', 'cancelled'), 'status')->is($i, 'customer')->sort('date_paid', true);
					$v->import('ref')->url(URL . 'admin/tickets/{{ref}}/');
					$v->column('{{event_name}}', 'Event');
					$v->column('{{first}} {{last}}', 'Name');
					$v->column('<span>{{status}}</span>', 'Status', 'status-{{status}}');
			}
			elseif ($pp->proud)
			{
				$ppp = $pp->page('edit')->title($customer->first . ' ' . $customer->last . ' - Edit');
				if ($ppp->active)
				{
					$v = $ppp->block('content')->title($customer->first . ' ' . $customer->last . ' - edit')->view('customer', 'edit')->id($i)->import('status', 'prefix', 'first', 'last', 'phone', 'email', 'street', 'locality', 'city', 'postcode', 'country', 'marketing')->redirectable($this->url . '?saved');
						$v->form->class('form-not-left');
						$v->field('dob', 'date')->title('D.O.B');
                        $v->button('back')->text('Back')->url($pp->url)->subtle();
						$v->button->text('Save customer');
				}
			}
		}

		$pp = $this->page('marketing');
		if ($pp->active)
		{
			$v = $pp->block('content')->view('customer', 'export')->import('prefix', 'first', 'last', 'email', 'phone', 'street', 'locality', 'city', 'postcode', 'country', 'marketing');
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