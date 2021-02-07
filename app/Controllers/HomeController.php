<?php
class HomeController extends SprBaseController {
    public function Index() {
        $viewbeg = [];
        $viewbeg["title"] = "Home Page";
        $this->View($viewbeg);
    }
}
