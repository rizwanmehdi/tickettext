<?php
    if ($this->active)
    {
        $b = $this->block('content')->title('Promoters');

            if (isset($_GET['added'])) $b->action()->success('Promoter added');
            elseif (isset($_GET['deleted'])) $b->action()->success('Promoter deleted');

            $v = $b->view('promoter', 'table')->import('active')->import('name')->url($this->url . '{{id}}/');
                $v->column('{{first}} {{last}}<br>{{phone}}', 'Contact');
                $v->query->sort('name');
            $bb = $b->block()->class('submit');
                $bb->button()->text('New promoter')->url('new/');
    }
    elseif ($this->proud)
    {
        $i = $this->next->_name;
        if (is_numeric($i)) if ($promoter = $this->promoter($i))
        {
            $pp = $this->page($i)->title($promoter->name);
            if ($pp->active)
            {
                $b = $pp->block('content')->title($promoter->name);

                $date_created = $promoter->date_created;

                if(strlen($date_created) > 0){
                    $date_created = date('d/m/Y', $date_created);
                }else{
                    $date_created = 'N/A';
                }

                if (isset($_GET['saved'])) $b->action()->success('Promoter saved');

                $v = $b->block()->title('Promoter summary <a href="' . $pp->url . 'edit/" class="button-header xxsmall right">Edit promoter</a>')->view('promoter', 'definition')->import('active', 'name');
                    $v->column('<a href="' . URL . $promoter->slug . '/" target="_blank">' . DOMAIN . '/' . $promoter->slug . '/</a>', 'Profile link');
                    $v->column($date_created,'Date Created');
                    $v->column('{{first}} {{last}}<br>{{phone}}<br><a href="mailto:{{email}}">{{email}}</a>', 'Contact details');
                    $v->column('{{street}}<br>{{locality}}<br>{{city}}<br>{{postcode}}', 'Contact address');
                    $v->query->is($i);

                $v = $b->block()->title('Promoter events')->view('event', 'table')->import('date_from')->url(URL . 'admin/events/{{id}}/');
                    $v->column('<strong>{{name}}</strong> <br>at {{venue_name}} in {{location_name}}', 'Event');
                    $v->column('<span>{{status}}</span><br>{{sold}} / {{quantity}}', 'Status', 'status-{{status}}');
                    $v->fields['date_from']->title('Date');
                    $v->query->not('deleted', 'status')->is($i, 'promoter')->sort('date_from', true);
            }
            elseif ($pp->proud)
            {
                $ppp = $pp->page('edit')->title($promoter->name . ' - Edit');
                if ($ppp->active)
                {
                    $v = $ppp->block('content')->title($promoter->name . ' - edit')->view('promoter', 'edit')->id($i)->import('active', 'name', 'fee', 'events', 'slug', 'image', 'logo', 'header', 'first', 'last', 'email', 'phone', 'street', 'locality', 'city', 'postcode', 'country', 'bank_name', 'bank_sort', 'bank_account', 'allowed_promoters_access', 'send_event_checklists')->redirectable($pp->url . '?saved');
                        $v->form->class('form-not-left');
                        $v->button('back')->text('Back')->url($pp->url)->subtle();
                }
            }
        }

        $pp = $this->page('new')->title('New Promoter');
        if ($pp->active)
        {
            $v = $pp->block('content')->title('New promoter')->view('promoter', 'register')->import('name', 'first', 'last', 'email', 'phone', 'street', 'locality', 'city', 'postcode', 'country', 'password', 'allowed_promoters_access', 'send_event_checklists')->redirectable($this->url . '?added');
                $v->form->class('form-not-left');
                $v->login(false);
                $v->fields['first']->title('Contact name');
                $v->fields['email']->title('Contact email');
                $v->fields['phone']->title('Contact phone');
                $v->fields['street']->title('Contact address');
                $v->button->text('Add promoter');
        }
    }
?>
