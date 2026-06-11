<?php

class GalleryController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        Auth::checkAuthentication();

        $this->View->render('gallery/index', array(
            'pictures' => GalleryModel::getOwnPictures(),
            'headline' => 'Meine Galerie',
            'show_upload' => true
        ));
    }

    public function upload()
    {
        Auth::checkAuthentication();

        GalleryModel::createPicture();
        Redirect::to('gallery/index');
    }

    public function delete($id)
    {
        Auth::checkAuthentication();

        GalleryModel::deletePicture($id);
        Redirect::to('gallery/index');
    }

    public function image($id)
    {
        $picture = GalleryModel::getPicture($id);
        GalleryModel::outputPicture($picture);
    }

    public function download($id)
    {
        $picture = GalleryModel::getPicture($id);

        if (GalleryModel::userCanViewPicture($picture)) {
            GalleryModel::countDownload($id);
        }

        GalleryModel::outputPicture($picture, true);
    }
}
