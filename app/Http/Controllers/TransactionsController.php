<?php

namespace App\Http\Controllers;

use App\Models\IndustrySector;
use App\Models\Members;
use App\Models\Regions;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Igaster\LaravelCities\Geo;

class TransactionsController extends Controller {
    public function index(Request $request) {
        $perPage = $request->input('limit');
        $sort = $request->input('sort');
        $sort = explode(',', $sort);
        if(count($sort) == 1)
            $sort = null;
        $search = $request->input('search');

        $data= Transaction::with('industrySector', 'member')
            ->when($search, function ($query) use ($search) {
                return $query->where('tombstone_title', 'like', "%$search%");
            })
            ->when($sort, function ($query) use ($sort) {
                return $query->orderBy($sort[0], $sort[1]);
            })
            ->paginate($perPage);

        return response()->json($data);
    }

    public function create() {
    }

    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => ['required','int'],
            'buyer_logo' => ['required','string'],
            'type_of_transaction' => ['required','string'],
            'industry_sector' => ['required', 'integer'],
            'detailed_business_desc' => ['required','string'],
            'transaction_size' => ['required','string'],
            'member_id' => ['required', 'integer'],
            'deal_manager' => ['required','string'],
            'tombstone_title' => ['required','string'],
            'keyphrase' => ['required','string'],
            'transaction_excerpt' => ['required','string'],
            'tombstone_top_image' => ['required','string'],
            'date_transaction' => ['required','date'],
            'tombstone_bottom_image' => ['required','string'],
            'notes' => ['nullable', 'string'],
            'side' => ['required','string']
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $slug = Str::slug($request->tombstone_title);
        $industrySector = IndustrySector::where('orbit_id', $request->industry_sector)->first();
        $memberID = Members::where('orbit_id', $request->member_id)->first();
        $transaction = Transaction::updateOrCreate(['orbit_id' => $request->id],[
            'buyer_logo' => $request->buyer_logo,
            'type_of_transaction' => $request->type_of_transaction,
            'industry_sector' => $industrySector->id,
            'detailed_business_desc' => stripslashes(base64_decode($request->detailed_business_desc)),
            'transaction_size' => $request->transaction_size,
            'member_id' => $memberID->id,
            'deal_manager' => $request->deal_manager,
            'tombstone_title' => $request->tombstone_title,
            'transaction_excerpt' => $request->transaction_excerpt,
            'keyphrase' => $request->keyphrase,
            'tombstone_top_image' => $request->tombstone_top_image,
            'tombstone_bottom_image' => $request->tombstone_bottom_image,
            'date_transaction' => $request->date_transaction,
            'notes' => $request->notes,
            'side' => $request->side,
            'slug' => $slug
        ]);

        return response()->json($transaction, 201);
    }

    public function show($id) {
        return Transaction::with('industrySector', 'member')->where('id', $id)->first();
    }

    public function detailsFrontend($slug) {
        $data = Transaction::with('industrySector', 'member')->where('slug', $slug)->where('approved','=',1)->first();
        $response = [
            'id' => $data->id,
            'country' => $data->member->region->name,
            'tombstone_title' => $data->tombstone_title,
            'date' => $data->date_transaction->format('m/Y'),
            'industrySector' => $data->industrySector,
            'tombstone_top_image' => $data->tombstone_top_image,
            'tombstone_bottom_image'=> $data->tombstone_bottom_image,
            'notes' => $data->notes,
            'type_of_transaction' => $data->type_of_transaction,
            'detailed_business_desc' => $data->detailed_business_desc,
            'slug' => $data->slug,
            'side' => $data->side,
            'next' => Transaction::where('id', '>', $data->id)->where('approved', 1)->first()->slug??null,
            'prev' => Transaction::where('id', '<', $data->id)->where('approved', 1)->first()->slug??null,
        ];
        return response()->json($response, 200);
    }

    public function transactionsPage() {
        //sides

        $buySide = ['Buy-Side'];
        $sellSide = ['Sell-Side'];
        $mbo = ['MBO','IPO','M&A Advisory', 'MBI'];
        $capitalRaises = ['Equity Raising', 'Debt Advisory', 'Equity-raise', 'CAPITAL RAISES', 'Bonds'];
        $restructuring = ['Restructuring'];

        $buySide = array_map('strtoupper', $buySide);
        $sellSide = array_map('strtoupper', $sellSide);
        $mbo = array_map('strtoupper', $mbo);
        $capitalRaises = array_map('strtoupper', $capitalRaises);
        $restructuring = array_map('strtoupper', $restructuring);

        $all = IndustrySector::with('transactionsLimited')->whereHas('transactionsLimited', function ($q) {
            $q->where('approved','=', 1);
        })->get();

        $buySideData = IndustrySector::with('transactionsLimited')->whereHas('transactionsLimited', function ($q) use($buySide){
            $q->where('approved','=', 1)->where(function ($q) use($buySide){
                $q->whereRaw('UPPER(side) IN (?)', [$buySide]);
            });
        })->get();

        $sellSideData = IndustrySector::with('transactionsLimited')->whereHas('transactionsLimited', function ($q) use($sellSide){
            $q->where('approved','=', 1)->where(function ($q) use($sellSide){
                $q->whereRaw('UPPER(side) IN (?)', [$sellSide]);
            });
        })->get();

        $mboData = IndustrySector::with('transactionsLimited')->whereHas('transactionsLimited', function ($q) use($mbo){
            $q->where('approved','=', 1)->where(function ($q) use($mbo){
                $q->whereRaw('UPPER(side) IN (?)', [$mbo]);
            });
        })->get();

        $capitalRaisesData = IndustrySector::with('transactionsLimited')->whereHas('transactionsLimited', function ($q) use($capitalRaises){
            $q->where('approved','=', 1)->where(function ($q) use($capitalRaises){
                $q->whereRaw('UPPER(side) IN (?)', [$capitalRaises]);
            });
        })->get();

        $restructuringData = IndustrySector::with('transactionsLimited')->whereHas('transactionsLimited', function ($q) use($restructuring){
            $q->where('approved','=', 1)->where(function ($q) use($restructuring){
                $q->whereRaw('UPPER(side) IN (?)', [$restructuring]);
            });
        })->get();


        $response = [
            'all' => $all,
            'buySide' => $buySideData,
            'sellSide' => $sellSideData,
            'mbo' => $mboData,
            'capitalRaises' => $capitalRaisesData,
            'restructuring' => $restructuringData,
        ];

        return $response;
    }

    public function transactionsPageLoadMore($side) {
        //sides

        $buySide = ['Buy-Side'];
        $sellSide = ['Sell-Side'];
        $mbo = ['MBO','IPO','M&A Advisory', 'MBI'];
        $capitalRaises = ['Equity Raising', 'Debt Advisory', 'Equity-raise', 'CAPITAL RAISES', 'Bonds'];
        $restructuring = ['Restructuring'];

        $buySide = array_map('strtoupper', $buySide);
        $sellSide = array_map('strtoupper', $sellSide);
        $mbo = array_map('strtoupper', $mbo);
        $capitalRaises = array_map('strtoupper', $capitalRaises);
        $restructuring = array_map('strtoupper', $restructuring);

        switch ($side){
            case 'all':
                $data = IndustrySector::with('transactions')->whereHas('transactions', function ($q) {
                    $q->where('approved','=', 1);
                })->get();
                break;
            case 'buySide':
                $data = IndustrySector::with('transactions')->whereHas('transactions', function ($q) use($buySide){
                    $q->where('approved','=', 1)->where(function ($q) use($buySide){
                        $q->whereRaw('UPPER(side) IN (?)', [$buySide]);
                    });
                })->get();
                break;
            case 'sellSide':
                $data = IndustrySector::with('transactions')->whereHas('transactions', function ($q) use($sellSide){
                    $q->where('approved','=', 1)->where(function ($q) use($sellSide){
                        $q->whereRaw('UPPER(side) IN (?)', [$sellSide]);
                    });
                })->get();
                break;
            case 'mbo-mbi-pe':
                $data = IndustrySector::with('transactions')->whereHas('transactions', function ($q) use($mbo){
                    $q->where('approved','=', 1)->where(function ($q) use($mbo){
                        $q->whereRaw('UPPER(side) IN (?)', [$mbo]);
                    });
                })->get();
                break;
            case 'capital-raises':
                $data = IndustrySector::with('transactions')->whereHas('transactions', function ($q) use($capitalRaises){
                    $q->where('approved','=', 1)->where(function ($q) use($capitalRaises){
                        $q->whereRaw('UPPER(side) IN (?)', [$capitalRaises]);
                    });
                })->get();
                break;
            case 'restructuring':
                $data = IndustrySector::with('transactions')->whereHas('transactions', function ($q) use($restructuring){
                    $q->where('approved','=', 1)->where(function ($q) use($restructuring){
                        $q->whereRaw('UPPER(side) IN (?)', [$restructuring]);
                    });
                })->get();
                break;
            default:
                $data = IndustrySector::with('transactions')->whereHas('transactions', function ($q) {
                    $q->where('approved','=', 1);
                })->get();
                break;
        }
        return $data;
    }



    public function showLatest() {
        $data = Transaction::where('approved','=', 1)->with('industrySector', 'member')->orderBy('date_transaction', 'desc')->limit(3)->get();
        $response = [];
        foreach ($data as $transaction){
            $response[] = [
                'id' => $transaction->id,
                'country' => $transaction->member->region->name,
                'date' => $transaction->date_transaction->format('m/Y'),
                'tombstone_top_image' => $transaction->tombstone_top_image,
                'tombstone_bottom_image'=> $transaction->tombstone_bottom_image,
                'type_of_transaction' => $transaction->type_of_transaction,
                'slug' => $transaction->slug,
                'side' => $transaction->side,
            ];
        }
        return response()->json($response, 200);
    }

    public function edit(Transaction $transaction) {
    }

    public function update(Request $request, $id) {
        $validator = Validator::make($request->all(), [
            'type_of_transaction' => ['required','string'],
            'industry_sector' => ['required', 'integer', 'exists:industry_sectors,id'],
            'detailed_business_desc' => ['string'],
            'transaction_size' => ['string'],
            'member_id' => ['required', 'integer'],
            'deal_manager' => ['string'],
            'tombstone_title' => ['required','string'],
            'tombstone_top_image' => ['required','string'],
            'tombstone_bottom_image' => ['required','string'],
            'approved' => ['required','string'],
            'side' => ['required','string'],
            'notes' => ['nullable', 'string'],
            'orbit_id' => ['string'],
        ]);

        $transaction = Transaction::find($id);


        if (!$transaction) {
            return response()->json("Transaction not found", 404);
        }

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $transaction->type_of_transaction = $request->type_of_transaction;
        $transaction->industry_sector = $request->industry_sector;
        $transaction->detailed_business_desc = stripslashes(base64_decode($request->detailed_business_desc));
        $transaction->transaction_size = $request->transaction_size;
        $transaction->member_id = $request->member_id;
        $transaction->deal_manager = $request->deal_manager;
        $transaction->tombstone_title = $request->tombstone_title;
        $transaction->approved = $request->approved;
        $transaction->tombstone_top_image = $request->tombstone_top_image;
        $transaction->tombstone_bottom_image = $request->tombstone_bottom_image;
        $transaction->side = $request->side;
        $transaction->orbit_id = $request->orbit_id??'1';
        $transaction->notes = $request->notes;
        $transaction->update();



        return response()->json($transaction, 200);

    }

    public function createByWp(Request $request) {
        $validator = Validator::make($request->all(), [
            'type_of_transaction' => ['required','string'],
            'industry_sector' => ['required', 'integer', 'exists:industry_sectors,id'],
            'detailed_business_desc' => ['string'],
            'transaction_size' => ['string'],
            'member_id' => ['required', 'integer'],
            'deal_manager' => ['string'],
            'tombstone_title' => ['required','string'],
            'tombstone_top_image' => ['required','string'],
            'tombstone_bottom_image' => ['required','string'],
            'approved' => ['required','string'],
            'side' => ['required','string'],
            'notes' => ['nullable', 'string'],
            'orbit_id' => ['string'],
            'date_transaction' => ['string','date_format:Y-m-d'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $transaction = new Transaction();
        $transaction->type_of_transaction = $request->type_of_transaction;
        $transaction->buyer_logo = "#";
        $transaction->keyphrase = Str::slug($request->tombstone_title);
        $transaction->date_transaction = Carbon::createFromFormat('Y-m-d', $request->date_transaction);
        $transaction->industry_sector = $request->industry_sector;
        $transaction->detailed_business_desc = base64_decode($request->detailed_business_desc);
        $transaction->transaction_size = $request->transaction_size;
        $transaction->member_id = $request->member_id;
        $transaction->deal_manager = $request->deal_manager;
        $transaction->tombstone_title = $request->tombstone_title;
        $transaction->approved = $request->approved;
        $transaction->tombstone_top_image = $request->tombstone_top_image;
        $transaction->tombstone_bottom_image = $request->tombstone_bottom_image;
        $transaction->side = $request->side;
        $transaction->notes = $request->notes;
        $transaction->orbit_id = $request->orbit_id??'1';
        $transaction->slug = Str::slug($request->tombstone_title);
        $transaction->save();



        return response()->json($transaction, 201);

    }

    public function destroy(Transaction $transaction) {
    }

    public function transactionByMember($member)
    {
        $member = urldecode($member);

        $transactionTypes = [
            'buySide' => ['Buy-Side'],
            'sellSide' => ['Sell-Side'],
            'mbo' => ['MBO', 'IPO', 'M&A Advisory', 'MBI'],
            'capitalRaises' => ['Equity Raising', 'Debt Advisory', 'Equity-raise', 'CAPITAL RAISES', 'Bonds'],
            'restructuring' => ['Restructuring']
        ];

        foreach ($transactionTypes as &$types) {
            $types = array_map('strtoupper', $types);
        }

        $member_object = Members::where('name', '=', $member)->first();
        if ($member_object == null) {
            return array_fill_keys(array_keys($transactionTypes), []);
        }

        $member_id = $member_object->id;

        // Enable query logging
        DB::enableQueryLog();

        $data = [];
        $data['all'] = $this->getTransactionsForMember($member_id);

        foreach (array_keys($transactionTypes) as $type) {
            $data[$type] = $this->getTransactionsForMember($member_id, $transactionTypes[$type]);
        }

        return $data;
    }

    private function getTransactionsForMember($member_id, $sides = [])
    {
        $query = IndustrySector::with('transactionsLimited')->whereHas('transactionsLimited', function ($query) use ($member_id, $sides) {
            $query->where('approved', '=', 1);
            $query->where('member_id', '=', $member_id);
            $query->orderBy('date_transaction', 'desc');

            if (!empty($sides)) {
                $query->whereRaw('UPPER(side) IN (?)', [$sides]);
            }
        });

        $results = [];
        foreach ($query->get() as $item) {
            $transactionLimited = [];
            $item = $item->toArray();

            foreach ($item['transactions_limited'] as $transaction) {
                if ($transaction['member_id'] == $member_id) {
                    $transactionLimited[] = $transaction;
                }
            }

            $item['transactions_limited'] = $transactionLimited;
            $results[] = $item;
        }


        return $results;
    }

    public function transactionFeatured($orbitID) {
        $data = Transaction::with('member')->where('orbit_id', '=', $orbitID)->where('approved', '=', 1)->first();
        return $data;
    }

    public function transactionsPageLoadMoreOffice($side, $member) {
        //sides
        $member = urldecode($member);

        $buySide = ['Buy-Side'];
        $sellSide = ['Sell-Side'];
        $mbo = ['MBO','IPO','M&A Advisory', 'MBI'];
        $capitalRaises = ['Equity Raising', 'Debt Advisory', 'Equity-raise', 'CAPITAL RAISES', 'Bonds'];
        $restructuring = ['Restructuring'];

        $buySide = array_map('strtoupper', $buySide);
        $sellSide = array_map('strtoupper', $sellSide);
        $mbo = array_map('strtoupper', $mbo);
        $capitalRaises = array_map('strtoupper', $capitalRaises);
        $restructuring = array_map('strtoupper', $restructuring);

        $member_id = Members::where('name', '=', $member)->first();
        if($member_id== null) {
            return $response = [
                'all' => [],
                'buySide' => [],
                'sellSide' => [],
                'mbo' => [],
                'capitalRaises' => [],
                'restructuring' => [],
            ];
        }
        $member_id = $member_id->id;

        switch ($side) {
            case 'all':
                $data = IndustrySector::with(['transactions' => function ($q) use ($member_id) {
                    $q->where('approved', '=', 1)
                        ->where('member_id', '=', $member_id)
                        ->orderBy('date_transaction', 'desc');
                }])->get();
                break;
            case 'buySide':
                $data = IndustrySector::with(['transactions' => function ($q) use ($buySide, $member_id) {
                    $q->where('approved', '=', 1)
                        ->where(function ($q) use ($member_id, $buySide) {
                            $q->whereRaw('UPPER(side) IN (?)', [$buySide])
                                ->where('member_id', '=', $member_id);
                        })
                        ->orderBy('date_transaction', 'desc');
                }])->get();
                break;
            case 'sellSide':
                $data = IndustrySector::with(['transactions' => function ($q) use ($sellSide, $member_id) {
                    $q->where('approved', '=', 1)
                        ->where(function ($q) use ($member_id, $sellSide) {
                            $q->whereRaw('UPPER(side) IN (?)', [$sellSide])
                                ->where('member_id', '=', $member_id);
                        })
                        ->orderBy('date_transaction', 'desc');
                }])->get();
                break;
            case 'mbo':
                $data = IndustrySector::with(['transactions' => function ($q) use ($mbo, $member_id) {
                    $q->where('approved', '=', 1)
                        ->where(function ($q) use ($member_id, $mbo) {
                            $q->whereRaw('UPPER(side) IN (?)', [$mbo])
                                ->where('member_id', '=', $member_id);
                        })
                        ->orderBy('date_transaction', 'desc');
                }])->get();
                break;
            case 'capitalRaises':
                $data = IndustrySector::with(['transactions' => function ($q) use ($capitalRaises, $member_id) {
                    $q->where('approved', '=', 1)
                        ->where(function ($q) use ($member_id, $capitalRaises) {
                            $q->whereRaw('UPPER(side) IN (?)', [$capitalRaises])
                                ->where('member_id', '=', $member_id);
                        })
                        ->orderBy('date_transaction', 'desc');
                }])->get();
                break;
            case 'restructuring':
                $data = IndustrySector::with(['transactions' => function ($q) use ($restructuring, $member_id) {
                    $q->where('approved', '=', 1)
                        ->where(function ($q) use ($member_id, $restructuring) {
                            $q->whereRaw('UPPER(side) IN (?)', [$restructuring])
                                ->where('member_id', '=', $member_id);
                        })
                        ->orderBy('date_transaction', 'desc');
                }])->get();
                break;
            default:
                $data = IndustrySector::with(['transactions' => function ($q) use ($member_id) {
                    $q->where('approved', '=', 1)
                        ->where('member_id', '=', $member_id)
                        ->orderBy('date_transaction', 'desc');
                }])->get();
                break;
        }

        $data = $data->filter(function ($item) {
            return $item->transactions->isNotEmpty();
        })->sortByDesc(function ($item) {
            return $item->transactions->first()->date_transaction;
        });

        return $data;
    }

    public function transactionByIndustry($industry) {
        $industry = urldecode($industry);

        $industry_id = IndustrySector::where('name', '=', $industry)->first();

        $industry_id = $industry_id->id;

        return IndustrySector::with('transactionsLimited')->whereHas('transactionsLimited', function ($q) use ($industry_id) {
            $q->where('approved','=', 1)->where('industry_sector', '=', $industry_id);
        })->get();
    }
    public function transactionByIndustryLoadMore($industry) {
        $industry = urldecode($industry);

        $industry_id = IndustrySector::where('name', '=', $industry)->first();

        $industry_id = $industry_id->id;

        return IndustrySector::with('transactions')->whereHas('transactionsLimited', function ($q) use ($industry_id) {
            $q->where('approved','=', 1)->where('industry_sector', '=', $industry_id);
        })->get();
    }

}
