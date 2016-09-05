<?php

    $site = $this->_site;
    $page = $this->url; // Reference to the 'active' page.



    if ($this->active)
    {
        $b = $this->block('content')->title('Agents');

            if (isset($_GET['added'])) $b->action()->success('Agent added');
            elseif (isset($_GET['deleted'])) $b->action()->success('Agent deleted');

            $v = $b->view('agent', 'table')->import('status')->url($this->url . '{{id}}/');

                $v->column("{{name}}", "Agent's name");
                $v->column("{{company}}", "Company");
                $v->column('{{email}}', 'Contact');
                $v->query->sort('name');
            $bb = $b->block()->class('submit');
                $bb->button()->text('New agent')->url('new/');
    }
    elseif ($this->proud)
    {
        $i = $this->next->_name;

        if (is_numeric($i)) if ($agent = $this->agent($i))
        {
            $pp = $this->page($i)->title($agent->name);

            // Perfomer Single
            if ($pp->active)
            {
                $b = $pp->block('content')->title($agent->name);

                // Saved Message
                if (isset($_GET['saved'])) $b->action()->success('Agent saved');

                // Top Definition Header
                $v = $b->block()->title('Agent - ' . $agent->name . ' <a href="' . $pp->url . 'edit/" class="button-header xxsmall right">Edit agent</a>')->view('agent', 'definition')->import('status', 'name');
                    $v->column('{{name}}<br>{{phone}}<br><a href="mailto:{{email}}">{{email}}</a>', 'Contact details');
                    $v->query->is($i);
            }
            // Edit agent
            elseif ($pp->proud)
            {
                $ppp = $pp->page('edit')->title($agent->name . ' - Edit');

                if ($ppp->active)
                {
                    $v = $ppp->block('content')->title($agent->name . ' - edit')->view('agent', 'edit')->id($i)
                        ->import('status','name', 'company', 'email', 'phone')
                        ->redirectable($this->url . '?added');

                    $v->form->class('form-not-left');
                    $v->button('back')->text('Back')->url($pp->url)->subtle();
                }

            }
        }
    }


    // New Agent.
    $pp = $this->page('new')->title('New Agent');

    if ($pp->active)
    {
        $agents = $this->query('agent')->rows();
        $options = array();

        foreach($agents as $agent)
        {
            $options[$agent->id] = $agent->name . ' - ' . $agent->email;
        }

        $v = $pp->block('content')->title('New agent')->view('agent', 'add')->import('status','name', 'company', 'email', 'phone');

        if(isset($_GET['return'])) $return_url = $_GET['return'];

        if(isset($return_url))
        {
            $v->redirectable($return_url);
        }

    }
