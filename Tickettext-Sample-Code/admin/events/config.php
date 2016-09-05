<?php

    // Shortcut vars.

        $site = $this->_site;
        $page = $site->page; // Reference to the 'active' page.
    // This page.
        load('view');

        $p_all = $this->page('all')->title('All Events');
        $p_new = $this->page('new')->title('New Event');
        $p_ticket = $this->page('ticket')->title('Free/Comp Tickets');

        // Attributes page.
        $p = $this->page('attributes')->title('Event Attribute Sets');


        if ($this->active)
        {
            if(isset($_GET['error']))
            {
                $b = $this->block('content');
                $b->action()->error('Sorry an error occured when trying to create a free ticket. Please try again');
                if(isset($_SESSION['customer']))
                {
                    unset($_SESSION['customer']);
                }
            }

                $b = $this->block('content')->title('Upcoming events <a href="attributes/" class="button-header xxsmall right">Event Attributes</a>');;
                if (isset($_GET['registered']))
                {
                    $b->action()->success('Welcome to Ticket Text!');
                    $s = $b->block()->class('submit')->class('center');
                        $s->button()->url($this->url . 'new/')->class('large')->text('Create your first event');
                }
                else
                {
                    if (isset($_GET['in'])) $b->action()->success('Welcome back!');
                    $v = $b->block()->view('event', 'table')->import('date_from')->url('{{id}}/');
                        $v->column('<strong>{{name}}</strong> <br>at {{venue_name}} in {{location_name}}', 'Event');
                        $v->column('<span>{{status}}</span><br>{{sold}} / {{quantity}}', 'Status', 'status-{{status}}');
                        $v->fields['date_from']->title('Date');
                        $v->query->not('deleted', 'status')->more(midnight() - (date('G') >= 6 ? 1 : 86401), 'date_from')->sort('date_from')->limit(50);
                    $s = $b->block()->class('submit');
                        $s->button()->url($this->url . 'new/')->text('Create new event');
                        $s->button()->url($this->url . 'all/')->text('Show all events')->subtle();
                }
        }
        elseif ($this->proud)
        {
            if ($p_all->active)
            {
                $n = (isset($_GET['n']) && is_numeric($_GET['n']) ? $_GET['n'] : 100);
                $o = (isset($_GET['o']) && is_numeric($_GET['o']) ? $_GET['o'] : 0);

                $b = $p_all->block('content')->title('All events');
                    $v = $b->block()->view('event', 'table')->import('date_from')->url('{{id}}/');
                        $v->column('{{name}}<br>at {{venue_name}} in {{location_name}}', 'Event');
                        $v->column('<span>{{status}}</span><br>{{sold}} / {{quantity}}', 'Status', 'status-{{status}}');
                        $v->query->sort('date_from')->limit($n)->offset($o);
                        $v->run();
                    $s = $b->block()->class('submit');
                        $s->button('back')->url($this->url)->text('Back')->subtle();
                        if ($o > 0) $s->button()->url($p_all->url . '?n=' . $n . '&o=' . ($o - $n < 0 ? 0 : $o - $n))->text('&larr;')->subtle();
                        if (count($v->rows) >= $n) $s->button()->url($p_all->url . '?n=' . $n . '&o=' . ($o + $n))->text('&rarr;')->subtle();
            }
            elseif ($p_new->active)
            {
                $fields = $this->object('event')->fields;

                $b = $p_new->block('content')->title('Step 1 of 2 - event details');
                    $f = $b->form('add')->class('form-not-left');
                        $f->nodes(array($fields['venue'], $fields['promoter'], $fields['category'], $fields['name'], $fields['subname'], $fields['partner_id'], $fields['keywords'], $fields['date_from'], $fields['time_from'], $fields['date_to'], $fields['time_to'], $fields['time'], $fields['image'], $fields['header'], $fields['flyer'], $fields['seating_image'], $fields['description'], $fields['ticket_comment'], $fields['subscribers_enable']));
                        $fields['date_from']->min_today();
                        $f->button->text('Continue');
                        $f->button('back')->url($this->url)->text('Back')->subtle();
                        if ($f->run())
                        {
                            $event = $this->event($f->get())->data('max', 25)->data('status', 'active');
                            
                            if ($event->insert()) {
                                $split = array();
                                $split= explode('-',$event->slug);
                                make_slug($event);
                                $slug = $event->slug;
                                //$slug .= '-' . date('dmY', $event->date_from);
                                $event->data('slug', $slug)->update();
                                redirect($this->url . $event->id . '/tier/?created');
                            }
                        }
            }
            elseif ($next = $this->next) if ($name = $next->_name) if (is_numeric($name)) if ($event = $this->event($name))
            {
                $p = $this->page($next)->title($event->name);
                if ($p->active)
                {
                    $b = $p->block('content')->title($event->name);

                        if (isset($_GET['delete'])) if ($event->data('status', 'deleted')->update()) redirect($this->url . '?deleted');

                        if(!isset($_GET['save'])){
                            $v = $b->block()->view('event', 'definition')
                                ->title('Event summary <a href="' . $p->url . 'edit/' . '" class="button-header xxsmall right">Edit event</a>' . '<a class="button-header xxsmall button-header-repeat-event" href="' . $p->url . 'duplicate/?id='. $event->id.'">Duplicate event</a>')
                                ->titled();
                            if (isset($_GET['created'])) $v->action->success('Event created');
                            elseif (isset($_GET['saved'])) $v->action->success('Event saved');
                            $v->query->is($event->id);
                            $v->import('name', 'subname', 'partner_id', 'location', 'venue', 'date_from', 'time_from', 'date_to', 'time_to', 'time');
                            $v->column('<a href="' . URL . 'admin/promoters/{{promoter}}/">{{promoter_name}}</a>', 'Promoter');
                            $v->column('<span>{{status}}</span>', 'Event status', 'status-{{status}}');
                            $v->column('<a href="' . URL . '{{promoter_slug}}/{{slug}}/" target="_blank">' . DOMAIN . '/{{promoter_slug}}/{{slug}}</span>', 'Event link');
                            $v->column($event->hidden? 'Yes' : 'No', 'Private');
                            $v->column('<span>{{sold}} / {{quantity}}</span>', 'Total sales');
                            
                            $v->column('<a href="' . URL . 'admin/payouts/'.$event->id.'" class="button-header xxsmall right">Click here</a>', 'Payouts');
//                            $v = $b->block()->view('event', 'definition')->title(''. '')->titled();


                             $q = $this->query('tier')->is($event->id, 'event')->not('', 'tier_attribute_sets');
                             if($q->num() > 0)
                            {
                                // Attributes for all tiers
                                $view = $site->simple_view('event_tier_attributes_table.phtml', array('script_path' => dirname(__FILE__)));

                                $v = $view->_output();
                                $v->site = $site;
                                $v->page = $p;

                                // Booking table.
                                $v->table_header = 'Tier attribute info';
                                $v->table_columns = array('Ticket Name', 'Attribute stats');

                                // Query
                                $sql = 'SELECT * FROM tier
                                    WHERE tier.event = ' . $event->id . ' ORDER BY tier.name DESC';


                                // View booking table.
                                $view->add_query($sql, 'tiers');
                                $b->block()->text($view->g_output()); // output final html
                            }




                            $v = $b->view('tier', 'table')->title('Ticket types <a href="tier/" class="button-header xxsmall right">Add ticket tier</a>')->titled();
                                $v->query->is($event->id, 'event')->sort('each');
                                $v->import('name', 'each')->url($p->url . 'tier/{{id}}/');
                                $v->fields['name']->title('Name');
                                $v->fields['each']->title('Price');
                                $v->column('{{sold}} / {{quantity}}', 'Sales');
                                $v->column('<span>{{status}}</span>', 'Tier status', 'status-{{status}}');

                                $v->run();
                                if (isset($_GET['ticket'])) $v->action->success('Ticket saved');
                                elseif (!$v->rows) $v->action->info('No tickets');

                            $v = $b->view('ticket', 'table')->title('Ticket sales <a class="button-header xxsmall" href="' . $p_ticket->url  . $event->id . '/new/">Create free ticket</a> <a href="sales/" class="button-header xxsmall right">Show all</a>')->titled();
                            
                                $v->query->is($event->id, 'event')->in(array('active', 'cancelled'), 'status')->sort('date_paid', true)->limit(20);
                                $v->import('ref')->url(URL . 'admin/tickets/{{ref}}/');
                                $v->column('{{first}} {{last}}', 'Name');
                                $v->column('{{quantity}} x {{tier_name}}', 'Ticket Title');
                                $v->column('<span>{{status}}</span>', 'Status', 'status-{{status}}');
                                $v->run();
                                if (!$v->rows) $v->action->info('No ticket sales yet');
                                //$v = $b->view('event', 'extended_table')->title(' <a class="button-header xxsmall" href="payouts/'. $event->id.'">Click here</a>')->titled();
                                

                            $s = $b->block()->class('submit');
                                $s->button()->url($p->url . '?delete')->text('Delete event')->subtle()->confirm();
                                $s->button('back')->url($this->url)->text('Back')->subtle();
                        }
                        elseif ($_GET['save'])
                        {
                            $start_dates = $_POST['dates_start']? $_POST['dates_start'] : '';
                            $end_dates = $_POST['dates_end']? $_POST['dates_end'] : '';

                            if($start_dates && $end_dates)
                            {
                                ///var_dump($start_dates);

                            } else redirect($p->url);
                        }
                }
                elseif ($p->proud)
                {
                    $p_bookings = $p->page('bookings');
                    $p_booking_add = $p_bookings->page('add')->title('Add new slot to event');
                    $p_booking_add_url = $p_booking_add->url;
                    $p_duplicate_event = $p->page('duplicate')->title('Duplicate Event');
                    $p_tier = $p->page('tier')->title($event->name - ' - Ticket');
                    $p_edit = $p->page('edit')->title($event->name . ' - Edit');
                    $p_venue = $p->page('venue')->title($event->name - ' - Venue');
                    $p_sales = $p->page('sales')->title($event->name - ' - Sales');
                    $p_print = $p->page('print');

                    if ($p_print->active) $event->e_print();
                    elseif ($p_edit->active) // from here
                    {

                        $v = $p_edit->block('content')->title($event->name)->view('event', 'edit')->id($event->id)->import('status', 'fee', 'promoter', 'venue', 'category', 'name', 'subname', 'partner_id', 'keywords', 'date_from', 'time_from', 'date_to', 'time_to', 'time', 'max', 'image', 'header', 'flyer', 'seating_image','slug', 'description', 'ticket_comment', 'session_price', 'hidden', 'background_image', 'subscribers_enable')->redirectable($p->url . '?saved');
                            $v->form->class('form-not-left');
                            //$v->field('featured', 'select')->title('Make appear in homepage')->options(array('0'=>'No','1'=>'Yes'));
                            $v->field('hidden', 'select')->title('Make event private? (only accessible via URL and will not appear in search)')->options(array('0'=>'No','1'=>'Yes'));
                            $v->field('session_price', 'currency')->title('Event Price')->placeholder('e.g &pound;9.99');
                            $v->field('background_image', 'file')->title('Background Image');
                            $v->button->text('Save event');
                            $v->button('back')->url($p->url)->text('Back')->subtle();
                            $v->run();
                    }
                    elseif ($p_tier->active)
                    {
                        $q = $this->query('attribute_set')->is($event->promoter, 'promoter')->columns(array('id', 'name'));

                        $attributes_sets = array();
                        $attribute_sets[''] = '';
                        foreach($q->rows() as $r) $attribute_sets[$r->id] = $r->name;

                        $b = $p_tier->block('content')->title((isset($_GET['created']) ? 'Step 2 of 2 - ticket details' : $event->name . ' - ticket'));
                            $v = $b->view('tier', 'add')->import('quantity', 'each', 'name', 'type', 'override', 'from_date', 'from_time', 'to_date', 'to_time', 'discount_allowed', 'tier_attribute_sets');
                                $v->fields['tier_attribute_sets']->title('Tier attribute set (Optional)')->options($attribute_sets);
                                $v->form->class('form-not-left');
                                $v->fields['from_date']->min_today();
                                $v->fields['to_date']->min_today();
                                $v->button->text(isset($_GET['created']) ? 'Create event' : 'Add ticket');
                                if (!isset($_GET['created'])) $v->button('back')->url($p->url)->text('Back')->subtle();
                                $v->single->data('event', $event->id)->data('status', 'available');
                                $v->redirectable($p->url . (isset($_GET['created']) ? '?created' : '?ticket'));
                    }
                    elseif ($p_tier->proud)
                    {
                        if ($next = $p_tier->next) if ($i = $next->_name) if (is_numeric($i)) if ($tier = $this->tier($i)) if ($tier->event == $event->id)
                        {
                            $q = $this->query('attribute_set')->is($event->promoter,'promoter')->columns(array('id', 'name'));

                            $attributes_sets = array();
                            $attribute_sets[''] = '';
                            foreach($q->rows() as $r) $attribute_sets[$r->id] = $r->name;

                            $pp = $p_tier->page($next)->title($event->name . ' - ' . $tier->name);
                                $b = $pp->block('content')->title($event->name . ' - ' . $tier->name);

                                    if (isset($_GET['delete']))
                                    {
                                        if ($tier->sold < 1) { if ($tier->data('quantity', 0)->delete()) redirect($p->url . '?deleted'); }
                                        else $b->action()->error('Cannot delete - tickets have already been sold');
                                    }

                                    $v = $b->view('tier', 'edit')->id($tier->id)->import('status', 'quantity', 'each', 'name', 'max', 'type', 'override', 'from_date', 'from_time', 'to_date', 'to_time', 'discount_allowed', 'tier_attribute_sets');
                                        $v->fields['tier_attribute_sets']->title('Tier attribute set (Optional)')->options($attribute_sets);
                                        $v->form->class('form-not-left');
                                        $v->button->text('Save ticket');
                                        $v->button()->subtle()->url($pp->url . '?delete')->text('Delete ticket')->confirm();
                                        $v->button('back')->url($p->url)->text('Back')->subtle();

                                        $v->redirectable($p->url . '?ticket');
                        }
                    }
                    elseif ($p_sales->active)
                    {

                        $tickets = array();

                        if(isset($_GET['cancel']))
                        {
                            $split = explode(',', $_GET['cancel']);


                            foreach($split as $key => $value)
                            {
                                if(is_numeric($value))
                                {
                                    $tickets[] = $this->ticket($value);
                                }
                            }

                            if(!empty($tickets))
                            {
                                foreach($tickets as $ticket)
                                {
                                    $qty = $ticket->quantity;

                                    $e = $this->event($ticket->event);
                                    $tier = $this->tier($ticket->tier);

                                    if($e)
                                    {
                                        $e->data('sold', $e->sold - $qty)->update();
                                        $e->data('quantity', $e->quantity + $qty)->update();
                                        $tier->data('sold', $tier->sold - $qty)->update();
                                        $tier->data('quantity', $tier->quantity + $qty)->update();
                                    }


                                    // cancel ticket
                                    $ticket->data('status', 'cancelled')->update();
                                }

                                redirect($p_sales->url);
                            }
                        }



                        $b = $p_sales->block('content')->title($event->name . ' - sales');

                            $v = $b->block()->view('ticket', 'table_special');
                                $v->query->in(array('active', 'cancelled'), 'status')->is($event->id, 'event')->sort('first')->sort('last');
                                $v->import('ref')->url(URL . 'admin/tickets/{{ref}}/');
                                $v->column('{{first}} {{last}}', 'Name');
                                $v->column('{{quantity}} x {{tier_name}}', 'Ticket Title');
                                $v->column('{{promo_code}}', 'Promo Code');
                                $v->column('{{email}}', 'Email');
                                $v->column('{{phone}}', 'Phone');
                                $v->column('{{utm_source}}', 'UTM');
                                $v->column('<span>{{status}}', 'Status', 'status-{{status}}');
                                $v->run();
                                if (!$v->rows) $v->action->info('No ticket sales yet');

                            $s = $b->block()->class('submit');
                                $s->button('print')->url($p->url . 'print/')->text('Print checklist');
                                $s->button('back')->url($p->url)->text('Back')->subtle();
                    }
                    /////////////////////////////////////////////////
                    // Bookings route /admin/events/{id}/booking
                    /////////////////////////////////////////////////
                    elseif($p_bookings->active)
                    {

                        $performer_fees = 0;
                        $bookings = $this->query('booking')->is($event->id, 'event')->rows();

                        foreach($bookings as $booking)
                        {
                            $performer_fees += $booking->performer_fee;
                        }


                        $p_bookings->title('Bookings for ' . $event->name);
                        $b = $p_bookings->block('content')->title($event->name);

                        if($event)
                        {
                            if(!$event->required_acts) $b->action()->error("Please add a budget and/or set a number of required acts for this show by clicking 'Edit event' in the top right");
                            if(!$event->budget) $b->action()->error("Please add a budget and/or set a number of required acts for this show by clicking 'Edit event' in the top right");
                        }

                        // Confirmations
                        if(isset($_GET['deleted'])) $b->action()->error('Deleted booking sucessfully.');
                        if(isset($_GET['saved'])) $b->action()->error('Saved booking sucessfully.');


                        if($performer_fees > $event->budget || !$event->budget) $b->block()->block()->class('warning-notice')->text('<div style="background: #ffe9ad !important;padding:10px;color:#fcb333;border:1px solid #e89200;"><h3 style="color:#e89200;"><strong>Warning! The total fees for confirmed performers at this event exceeds the event budget!</strong></h3></div>');

                        $v = $b->block()->view('event', 'definition')->title('Event summary'. '<a href="' . $p_bookings->url . 'edit" class="button-header xxsmall right">Edit event</a>')->titled();
                        $v->import('name', 'subname', 'location', 'venue', 'date_from', 'time_from', 'date_to', 'time_to', 'time', 'required_acts', 'budget');
                        $v->column('<a href="' . URL . 'admin/promoters/{{promoter}}/">{{promoter_name}}</a>', 'Promoter');
                        $v->column('<span>{{status}}</span>', 'Event status', 'status-{{status}}');
                        $v->column('<a href="' . URL . '{{promoter_slug}}/{{slug}}/" target="_blank">' . URL . '{{promoter_slug}}/{{slug}}</span></a>', 'Event link');

                        $q = $v->query;
                        $q->is($event->id, 'id');
                        $v->run();

                        // Block below
                        $bb = $b->block();

                        $q = $site->query('booking')->is($event->id, 'event');



                        /////////////////////////////////////////////////
                        // Booking route /admin/events/{id}/bookings
                        /////////////////////////////////////////////////
                        if($q->num() <= 0)
                        {
                            $bb->block()->text('No performer slots have been added yet. <a class="button-header xxsmall right add-slot-button" href="' . $p_bookings->url. 'add/">Add slot</a>');
                        }
                        else
                        {
                            $b_cnt = 0;

                            $bb = $b;

                            $rows = $q->rows();
                            $total = count($rows);

                            $b = $p_bookings->block('content')->title('Bookings');
                            $view = $site->simple_view('booking_slots_for_event.phtml', array('script_path' => dirname(__FILE__)));

                            $v = $view->_output();
                            $v->site = $site;
                            $v->page = $p_bookings;
                            $v->table_columns = array('Performer','Slot type', 'Fee', 'Confirmed');
                            $v->table_header = 'Bookings';

                            $sql = 'SELECT b.id, b.event, b.performer, b.performer_fee, b.slot_type, b.confirmed
                                   FROM booking as b WHERE b.event = '.$event->id.' LIMIT 0, 20';


                            $view->add_query($sql, 'booking_slots_for_event');
                            $b->block()->text($view->g_output()); // output final html
                        }

                    }
                    elseif($next = $p_bookings->next)
                    {
                        $next = $p_bookings->next;


                        if($next->_name == 'add')
                        {
                            $p_booking_add = $p_bookings->page($next);
                            $b = $p_booking_add->block('content')->title('Add booking slot to event');

                            $v = $b->view('booking', 'add')
                                   ->title('Booking slot')
                                   ->titled()
                                   ->import('confirmed', 'performer' ,'slot_type', 'performer_fee', 'accommodation_type', 'accommodation_arranged','arrival_time', 'set_start_time', 'set_end_time', 'act_paid')
                                   ->url($p_bookings->url . 'bookings/{{id}}/');

                                $v->field('performer');

                                //////////////////////////
                                // TEMP FIX !!!!!!!!!!!
                                //////////////////////////
                                global $event_id;
                                $event_id = $event->id;
                                /////////////////////////
                                /// END OF TEMP FIX
                                /////////////////////////

                                $v->field('event', 'text')->title('Event (required)')->required()->set($event->id)->class('hidden');  // Event

                                $v->button->text('Add');
                                $v->button('back')->text('Cancel')->url($p_bookings->url)->subtle();
                                $v->redirectable($p_bookings->url . '?saved');

                        }
                        elseif(is_numeric($next->_name))
                        {

                            //////////////////////////
                            // TEMP FIX !!!!!!!!!!!
                            //////////////////////////
                            global $booking_id;
                            $booking_id = $next->_name; // booking id from URL.
                            global $event_id;
                            $event_id = $event->id;

                            //var_dump($booking_id);

                            /////////////////////////
                            /// END OF TEMP FIX
                            /////////////////////////

                            $pp = $p_bookings->page($next);

                            // delete action
                            if(isset($_GET['delete']))
                            {
                                $this->booking($booking_id)->delete();
                                redirect($p_bookings->url . '?deleted');
                            }



                            if($next = $pp->next)
                            {
                                $p_bookings_edit = $p_bookings->page($next);

                                $b = $p_bookings_edit->block('content');



                                $v = $b->view('booking', 'edit')
                                    ->title('Booking slot')
                                    ->titled()
                                    ->import('confirmed', 'performer' , 'slot_type', 'performer_fee', 'accommodation_type', 'accommodation_arranged', 'arrival_time', 'set_start_time', 'set_end_time', 'act_paid')
                                    ->id($booking_id)
                                    ->title('Booking slot')->titled();

                                $v->button->text('Save');
                                $v->button('back')->text('Cancel')->url($p_bookings->url)->subtle();
                                $v->redirectable($p_bookings->url . '?saved');
                            }
                        }
                        elseif($next->_name == 'edit')
                        {
                            $p_bookings_edit = $p_bookings->page($next);

                            $b = $p_bookings_edit->block('content');

                            $v = $b->view('event', 'edit')
                                    ->title('Event')
                                    ->titled()
                                    ->import('budget', 'required_acts', 'manager')
                                    ->id($event->id);


                            $v->button->text('Continue');
                            $v->button('back')->text('Cancel')->url($p_bookings->url)->subtle();
                            $v->redirectable($p_bookings->url . '?saved');

//                            if ($f->run())
//                            {
//                                $event = $this->event($f->get())->data('budget', $f->get('budget'))->data('required_acts', $f->get('required_acts'));
//                           }
                        }

                    }
                    elseif ($p_duplicate_event->active)
                    {
                        // Step 1.

                            if(isset($_GET['id']) && !$_POST)
                            {
                                $event_id = $_GET['id'];
                                $events = $site->query('event')->sort('status', true)->sort('date_from')->is($event_id, 'id')->more((date('G') >= 6 ? midnight() - 1 : midnight() - 86401), 'date_from')->not('deleted', 'status');
                                $dates_from = array();
                                foreach($events->rows() as $event) $dates_from[] = $event->date_from;
                                $form_url = $p_duplicate_event->url . '?save';
                                $b = $p_duplicate_event->block('content')->title('Step 1 of 2 - Select your dates');
                                $b->block('cal')->data('event_id', $event_id)->data('form_url', $form_url)->data('date_from', $dates_from)->file('repeat_events.php');

                            }else
                            {
                                if($_POST && isset($_GET['save'])){
                                    $dates = trim($_POST['dates_start']);
                                    $dates = explode(',', $dates);
                                    $converted = array();
                                    $event_ids = array();
                                    $duplicate_event = array();

                                    foreach($dates as $date_start)
                                    {
                                        if($date_start != '' && $date_start != ','){
                                            $timestamp = strtotime($date_start);
                                            $event_id = $_GET['id'];

                                            if(!$timestamp <= time()){
                                                $event = $this->event($event_id);
                                                if($event->id){
                                                    $duplicate_event['category'] = $event->category;
                                                    $duplicate_event['status'] = $event->status;
                                                    $duplicate_event['fee'] = $event->fee;
                                                    $duplicate_event['promoter'] = $event->promoter;
                                                    $duplicate_event['venue'] = $event->venue;
                                                    $duplicate_event['name'] = $event->name;
                                                    $duplicate_event['subname'] = $event->subname;
                                                    $duplicate_event['keywords'] = $event->keywords;
                                                    $duplicate_event['date_from'] = $timestamp;
                                                    $duplicate_event['time_from'] = $event->time_from;
                                                    $duplicate_event['date_to'] = $timestamp;
                                                    $duplicate_event['time_to'] =  $event->time_to;
                                                    $duplicate_event['time'] = $event->time;
                                                    $duplicate_event['max'] = $event->max;
                                                    $duplicate_event['image'] = $event->image;
                                                    $duplicate_event['header'] = $event->header;
                                                    $duplicate_event['flyer'] = $event->flyer;
                                                    $duplicate_event['seating_image'] = $event->seating_image;
                                                    $duplicate_event['ticket_comment'] = $event->ticket_comment;


                                                    $split= explode('-',$event->slug);
                                                    $slug = $event->slug;

                                                    if(!empty($split)){
                                                        foreach($split as $key => $s){
                                                            if(is_numeric($s) && strlen($s) == 8){
                                                                unset($split[$key]);
                                                            }
                                                        }

                                                        $slug = implode('-', $split);
                                                    }

                                                    $duplicate_event['slug'] = $slug . '-' . date('dmY', $timestamp);

                                                    $rows = $this->query('event')->rows();
                                                    $i = 1;
                                                    $slugg = $duplicate_event['slug'];

                                                    foreach($rows as $r )
                                                    {
                                                        if($slugg == $r->slug)
                                                        {
                                                            $duplicate_event['slug'] = $duplicate_event['slug'] . '-' . $i;
                                                            $i++;
                                                        }
                                                    }
                                                    $duplicate_event['subscribers_enable'] = $event->subscribers_enable;
                                                    $duplicate_event['location'] = $event->location;
                                                    $duplicate_event['ballot'] = $event->ballot;
                                                    $duplicate_event['ballot_type'] = $event->ballot_type;
                                                    $duplicate_event['budget'] = $event->budget;
                                                    $duplicate_event['required_acts'] = $event->required_acts;
                                                    $duplicate_event['description'] = $event->description;
                                                    $duplicate_event['hidden'] = $event->hidden;
                                                    $duplicate_event['background_image'] = $event->background_image;
                                                    $duplicate_event['promoter_cached'] = $event->promoter;
                                                    $duplicate_event['venue_cached'] = $event->venue_cached;
                                                    $duplicate_event['location_cached'] = $event->location_cached;
                                                    $duplicate_event['location_slug'] = $event->location_slug;
                                                    $duplicate_event['category_cached'] = $event->category_cached;
                                                    $duplicate_event['category_name'] = $event->category_name;
                                                    $duplicate_event['category_slug'] = $event->category_slug;
                                                    $duplicate_events = $this->event($duplicate_event);
                                                    $duplicate_events->insert();

                                                    $id = $duplicate_events->id;

                                                    $tiers = $this->query('tier')->is( $event->id ,'event');
                                                    $copy = array();

                                                    foreach($tiers->rows() as $tier)
                                                    {
                                                        $copy['event'] = $id;
                                                        $copy['status'] = $tier->status;
                                                        $copy['name'] = $tier->name;
                                                        $copy['quantity'] = $tier->quantity;
                                                        $copy['sold'] = 0;
                                                        $copy['max'] = $tier->max;
                                                        $copy['from'] = $tier->from;
                                                        $copy['to'] = $tier->to;
                                                        $copy['each'] = $tier->each;
                                                        $copy['type'] = $tier->type;
                                                        $copy['override'] = $tier->override;
                                                        $copy['tier_attribute_sets'] = $tier->tier_attribute_sets;

                                                        $tier_copy = $site->tier($copy);
                                                        $tier_copy->insert();

                                                    }
                                                }
                                            }
                                        }
                                    }
                                }

                                redirect(URL . 'admin/events');
                            }
                    }
                }
            }

        }
?>
