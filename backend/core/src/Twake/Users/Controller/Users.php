<?php


namespace Twake\Users\Controller;

use Common\BaseController;
use Common\Http\Response;
use Common\Http\Request;

class Users extends BaseController
{

    public function search(Request $request)
    {

        $scroll_id = $request->request->get("scroll_id");
        $repository = "Twake\Users:User";
        $options = $request->request->get("query", $request->request->get("options", Array()));

        if (is_string($options)) {
            $options = Array("name" => $options);
        }

        if (isset($scroll_id) && isset($repository)) {
            $globalresult = $this->get('globalsearch.pagination')->getnextelement($scroll_id, $repository);
        } else {
            $globalresult = $this->get("app.users")->search($options);
        }

        $data = Array("data" => $globalresult);

        return new Response($data);

    }

    public function getById(Request $request)
    {

        $data = Array(
            "errors" => Array(),
            "data" => Array()
        );

        $id = $request->request->get("id");
        $user = $this->get("app.users")->getById($id);

        if ($user) {
            $data["data"] = $user;
        } else {
            $data["errors"][] = "user_was_not_found";
        }

        return new Response($data);

    }

}
