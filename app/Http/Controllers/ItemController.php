<?php

namespace App\Http\Controllers;

use App\itemReview;
use App\Item;
use App\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ItemController extends Controller
{

    public function getCategories(): JsonResponse
    {
        return response()->json(Item::select('Categories')->distinct()->get());
    }
    /**
     * Function  that searches for items from user input
     */
    public function checkFavourites(Request $request)
    {
        $checkItems = [];
        $favourites = $request->input('favourites');

        if ($favourites == null) {
            return "empty";
        }
        for ($i = 0; $i < count($favourites); $i++) {

            $item = Item::select('availableQuantity')->where('itemId', $favourites[$i]["itemId"])->get();
            $checkIfItemExists = Item::where('itemId', $favourites[$i]["itemId"])->get();


            if (count($checkIfItemExists) == 0) {
                array_push($checkItems, $favourites[$i]["itemId"]);
            } else {
                if ($item[0]->availableQuantity == 0 || $item[0]->availableQuantity == null) {
                    array_push($checkItems, $favourites[$i]["itemId"]);
                }
            }
            return $checkItems;
        }
    }

    public function searchForItems(Request $request)
    {
        $data = $request->input('data');
        $returnData = Item::where('itemName', 'like', '%' . $data . '%')->get();

        return $returnData;
    }

    public function changeItem(Request $request)
    {

        $discount = $request->input('Discount');
        $itemId = $request->input('itemId');
        $itemName = $request->input('itemName');
        $Quantity = $request->input('količina');
        $itemPrice = $request->input('cena');
        $Description = $request->input('Description');


        //Validation
        $rules = [
            'cena' => 'required|numeric',
            'količina' => 'required|numeric'
        ];

        $customMessage = [
            'numeric' => "Napačna vrednsot pri :attribute"
        ];

        //Validates if provided items are correct type
        $this->validate($request, $rules, $customMessage);
        $change = null;
        if ($discount != null) {
            $change = Item::where('itemId', $itemId)
                ->update([
                    'itemName' => $itemName,
                    'availableQuantity' => $Quantity,
                    'itemPrice' => $itemPrice,
                    'itemDescription' => $Description,
                    'isOnSale' => 1,
                    'discount' => $discount,
                    'discountItemPrice' => ((100 - $discount) * $itemPrice) / 100
                ]);
        } else {
            $change = Item::where('itemId', $itemId)
                ->update([
                    'itemName' => $itemName,
                    'availableQuantity' => $Quantity,
                    'itemPrice' => $itemPrice,
                    'itemDescription' => $Description,
                    'isOnSale' => 0,
                    'discount' => 0
                ]);
        }

        if ($change) {
            return 1;
        }
        return 0;
    }

    //Delists item from store
    public function delistItem(Request $request)
    {

        if ($request->input('status') == "Remove") {
            $item = Item::find($request->input('itemId'));
            $item->delisted = 1;
            $item->save();

        } else {
            $item = Item::find($request->input('itemId'));
            $item->delisted = 0;
            $item->save();
        }
    }

    public function deleteItem(Request $request)
    {
        $id = $request->input('itemId');
        $name = Item::select('itemName')->where('itemId', $id)->get();

        //Get all files that are stored in that directory
        $files = Storage::disk('public')->allFiles('products/' . $name[0]->itemName);

        //Delete every one of them
        for ($i = 0; $i < count($files); $i++) {
            Storage::disk('public')->delete($files[$i]);
        }

        //After that delete folder
        Storage::disk('public')->deleteDirectory('products/' . $name[0]->itemName);

        //After folder was deleted delte item
        $delete = Item::destroy($id);

        //Delete all orders and item reviews where selected item is present
        Order::where('itemId', $id)->delete();
        itemReview::where('itemId', $id)->delete();

        if ($delete) {
            return 1;
        }
        return 0;
    }

    public function getImages($id)
    {
        $name = Item::select('itemName')->where('itemId', $id)->get();
        $dirName = $name[0]->itemName;
        $files = Storage::disk('public')->files('products/' . $dirName);

        return $files;
    }

    public function addItem(Request $request)
    {
        $itemName = $request->input('itemName');
        $itemPrice = $request->input('cena');
        $Quantity = $request->input('kolicina');
        $Dimension = $request->input('Dimensions');
        $Categorie = $request->input('Categorie');
        $Color = $request->input('Color');
        $primaryImg = $request->input('itemImg');
        $images = $request->file('images');
        $Description = $request->input('Description');


        //Validation
        $rules = [
            'cena' => 'required|numeric',
            'kolicina' => 'required|numeric'
        ];

        $customMessage = [
            'numeric' => "Napačna vrednsot pri :attribute"
        ];

        //Validates if provided items are correct type
        $this->validate($request, $rules, $customMessage);


        if (Item::where('itemName', $itemName)->count() == 1) {
            return "Izdelek že obstaja!";
        } else {
            $path = public_path('storage\products/' . $itemName);

            mkdir($path, 0777, true);


            foreach ($images as $image) {
                if ($image->getClientOriginalName() == $primaryImg) {
                    $image->storeAs('public/products/' . $itemName, $primaryImg);
                } else {
                    $image->store('public/products/' . $itemName);
                }
            }


        };


        $item = new Item;

        $item->itemName = $itemName;
        $item->itemPrice = $itemPrice;
        $item->availableQuantity = $Quantity;
        $item->itemImg = $primaryImg;
        $item->itemImgDir = $itemName;
        $item->dimensions = $Dimension;
        $item->categorie = $Categorie;
        $item->colors = $Color;
        $item->itemDescription = $Description;

        $checkInsert = $item->save();

        if ($checkInsert) {
            return 1;
        }
        return 0;
    }

    public function addReview(Request $request)
    {
        $Comment = $request->input('Comment');
        $Rating = $request->input('Rating');
        $productId = $request->input('productId');
        $name = $request->input('Name');
        $surname = $request->input('Surname');
        $email = $request->input('Email');
        $date = date("Y-m-d H:i:s");

        $checkIfUserAlreadyPosted = itemReview::where('Email', $email)->count();

        if ($checkIfUserAlreadyPosted > 0) {
            return "User Already posted";
        }

        $addItemReview = new itemReview();

        $addItemReview->itemId = $productId;
        $addItemReview->Name = $name;
        $addItemReview->Surname = $surname;
        $addItemReview->comment = $Comment;
        $addItemReview->postTime = $date;
        $addItemReview->Email = $email;
        $addItemReview->rating = $Rating;


        if ($addItemReview->save()) {
            $newOverAllRating = 0;
            $getItemReviews = itemReview::select('rating')->where('itemId', $productId)->get();

            for ($i = 0; $i < count($getItemReviews); $i++) {
                $newOverAllRating += $getItemReviews[$i]->rating;
            }
            $newOverAllRating /= count($getItemReviews);
            $item = Item::where('itemId', $productId)
                ->update(['overAllrating' => $newOverAllRating]);

            return 1;
        }
        return 2;
    }

}