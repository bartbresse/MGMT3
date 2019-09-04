<?php

use Phalcon\Mvc\User\Component;

class LanguageHandler2 extends Component
{
    private $settings;
    private $words;

    public function __construct($settings)
    {
        $this->settings = $settings;
    }

    public function buildView($view,$words)
    {
        $base = $this->settings->base;
        $used = $this->settings->used;

        $view = Frontendview::findFirst('Name = "'.$view.'"');

        foreach($words as $word)
        {
            $word = strtolower($word);

            $language = Language::findFirst($base.' = "'.$word.'"');
            if(!$language){
                $language = new Language();
                $language->Dutch = 'tbt';
                $language->English = 'tbt';
                $language->German = 'tbt';

                $language->$base = $word;
                if(!$language->save())
                {
                    print_r($language->getMessages());
                    die();
                }
            }

            if(!LanguageHasFrontendview::findFirst('Language_idLanguage = '.$language->idLanguage.' AND Frontendview_idFrontendview = '.$view->idFrontendview)){

                $lhfev = new LanguageHasFrontendview();
                $lhfev->Language_idLanguage = $language->idLanguage;
                $lhfev->Frontendview_idFrontendview = $view->idFrontendview;
                if(!$lhfev->save()){
                    print_r($lhfev->getMessages());
                    die();
                }

            }

        }
    }

    private function buildQuery($key,$array)
    {
        $str = '';
        foreach($array as $index => $value){
            if($index > 0){ $str .= ' OR '; }
            $str .= $key.' = "'.strtolower($value).'"';
        }
      //  echo $str.'<br /><br /><br />';
        return $str;
    }

    private function addNewWords($words)
    {
        $base = $this->settings->base;
        $used = $this->settings->used;

        foreach($words as $word)
        {
            $word = strtolower($word);
            if(!Language::findFirst($this->settings->base.' = "'.$word.'"'))
            {
                $language = new Language();

                $language->Dutch = 'tbt';
                $language->English = 'tbt';
                $language->German = 'tbt';

                $language->$base = $word;
                if(!$language->save())
                {
                    print_r($language->getMessages());
                    die();
                }
            }
        }
    }

    public function processArray($words)
    {
        $base = $this->settings->base;
        $used = $this->settings->used;

        if($this->settings->listenForNewWords) {
            $this->addNewWords($words);
        }

        foreach (Language::find($this->buildQuery($this->settings->base, $words)) as $entry) {
            $this->words[$entry->$base] = ucfirst($entry->$used);
        }

        return $this->words;
    }

    public function processView($view)
    {
        $base = $this->settings->base;
        $used = $this->settings->used;

        $viewentity = Frontendview::findFirst('Name = "'.$view.'"');
        if($viewentity) {
            foreach ($viewentity->LanguageHasFrontendview as $lhv) {
                $this->words[$lhv->Language->$base] = $lhv->Language->$used;
            }
        }
        return $this->words;
    }

    public function get()
    {
        return $this->words;
    }
}
