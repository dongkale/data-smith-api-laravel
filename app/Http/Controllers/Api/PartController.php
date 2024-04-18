<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

use App\Models\Part;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Data Smith Api Documentation",
 *     description="Data Smith  Api Documentation",
 *     @OA\Contact(
 *         name="Lee Dong Kwan",
 *         email="dklee@lennon.co.kr"
 *     ),
 *     @OA\License(
 *         name="Apache 2.0",
 *         url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *     )
 * ),
 * @OA\Server(
 *     url="/api/part",
 * ),
 */
class PartController extends Controller
{
    function isJson($string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * @OA\Get (
     *     path="/list",
     *     summary="",
     *     tags={"- 전체 리스트 요청"},
     *     description="전체 리스트를 요청한다, header.X-API-Key에 Api Key 필요",
     *     security={{"api-key": {}},},
     *     @OA\Response(
     *         response="200",
     *         description="결과값",
     *         @OA\JsonContent(
     *             @OA\Property(property="result_code", type="int", example="0", description="성공:0, 실패:-1"),
     *             @OA\Property(property="result_message", type="string", example="Success", description="성공:Success, 실패:에러메세지"),
     *             @OA\Property(property="result_data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="name", type="string", description="이름", example="이름 1"),
     *                      @OA\Property(property="description", type="string", description="설명", example="설명 1"),
     *                      @OA\Property(property="data_json", type="string", description="JSON 문자열", example="{}"),
     *                  ),
     *                  @OA\Items(
     *                      @OA\Property(property="name", type="string", description="이름", example="sample_2"),
     *                      @OA\Property(property="description", type="string", description="설명", example="설명 2"),
     *                      @OA\Property(property="data_json", type="string", description="JSON 문자열", example="{}"),
     *                  ),
     *            )
     *         )
     *     )
     * )
     */
    public function index()
    {
        try {
            $parts = Part::all();
        } catch (\Exception $e) {
            Log::error("Database Query Fail: " . $e->getMessage());
            return response()->json([
                "result_code" => -1,
                "result_message" => "Database Query Fail",
            ]);
        }

        $data = [];
        foreach ($parts as $part) {
            $data[] = [
                "name" => $part->name,
                "description" => $part->description,
                "data_json" => $part->data_json,
            ];
        }

        return response()->json([
            "result_code" => 0,
            "result_message" => "Success",
            "result_data" => $data,
        ]);
    }

    /**
     * @OA\Get (
     *     path="/listByName/{name}",
     *     summary="",
     *     tags={"- 지정 파츠 요청(by name)"},
     *     description="지정 파츠를 요청 한다, header.X-API-Key에 Api Key 필요",
     *     security={{"api-key": {}},},
     *     @OA\Parameter(
     *         description="지정 파츠 name",
     *         in="path",
     *         name="name",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         @OA\Examples(example="string", value="name_01", summary="paramter"),
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="결과값",
     *          @OA\JsonContent(
     *              @OA\Property(property="result_code", type="int", example="0", description="성공:0, 실패:-1"),
     *              @OA\Property(property="result_message", type="string", example="Success", description="성공:Success, 실패:파츠가 존재 하지 않음"),
     *              @OA\Property(property="result_data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="name", type="string", description="이름", example="이름 1"),
     *                      @OA\Property(property="description", type="string", description="설명", example="설명 1"),
     *                      @OA\Property(property="data_json", type="string", description="JSON 문자열", example="{}"),
     *                  ),
     *             )
     *         )
     *     )
     * )
     */
    public function partByName($name)
    {
        try {
            $part = Part::where("name", "=", $name)->first();
        } catch (\Exception $e) {
            Log::error("Database Query Fail: " . $e->getMessage());
            return response()->json([
                "result_code" => -1,
                "result_message" => "Database Query Fail",
            ]);
        }

        if (empty($part)) {
            return response()->json([
                "result_code" => -1,
                "result_message" => "Part not found",
            ]);
        }

        return response()->json([
            "result_code" => 0,
            "result_message" => "Success",
            "result_data" => [
                "name" => $part->name,
                "description" => $part->description,
                "data_json" => $part->data_json,
            ],
        ]);
    }

    /**
     * @OA\Post (
     *     path="/save",
     *     summary="",
     *     tags={"- 파츠 저장"},
     *     description="파츠를 저장 한다, 지정 이름, 설명을 json data로 요청하면 저장한다. header.X-API-Key에 Api Key 필요",
     *     security={{"api-key": {}},},
     *     @OA\RequestBody(
     *          @OA\JsonContent(
     *              @OA\Property(property="name", type="string", example="이름 1", description="이름 1"),
     *              @OA\Property(property="description", type="string", example="설명 1", description="설명 1"),
     *              @OA\Property(property="data_json", type="string", example="{}", description="{}")
     *         )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="결과값",
     *          @OA\JsonContent(
     *              @OA\Property(property="result_code", type="int", example="0", description="성공:0, 실패:-1"),
     *              @OA\Property(property="result_message", type="string", example="Success", description="성공:EMPTY, 실패:에러메세지(데이터 포맷 미 일치"),
     *              @OA\Property(property="result_data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="int", description="Id", example="Id"),
     *                      @OA\Property(property="name", type="string", description="이름", example="이름 1"),
     *                      @OA\Property(property="description", type="string", description="설명", example="설명 1"),
     *                      @OA\Property(property="data_json", type="string", description="JSON 문자열", example="{}"),
     *                  ),
     *             )
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "name" => "required|max:128",
            "description" => "required|max:512",
            "data_json" => "required",
        ]);

        if ($validator->fails()) {
            return response()->json([
                "result_code" => -1,
                "result_message" => $validator->errors(),
            ]);
        }

        if (!$this->isJson($request->data_json)) {
            return response()->json([
                "result_code" => -1,
                "result_message" => "'data_json' Invalid Json String Format",
            ]);
        }

        try {
            $part = Part::create($request->all());
        } catch (\Exception $e) {
            Log::error("Database Save Fail: " . $e->getMessage());
            return response()->json([
                "result_code" => -1,
                "result_message" => "Database Save Fail",
            ]);
        }

        return response()->json([
            "result_code" => 0,
            "result_message" => "Success",
            "result_data" => [
                "id" => $part->id,
                "name" => $part->name,
                "description" => $part->description,
                "data_json" => $part->data_json,
            ],
        ]);
    }

    /**
     * @OA\Put (
     *     path="/updateByName/{name}",
     *     summary="",
     *     tags={"- 파츠 수정(by name)"},
     *     description="지정한 파츠를 수정한다, 지정 이름으로 설명, json data로 요청하면 갱신한다. header.X-API-Key에 Api Key 필요",
     *     security={{"api-key": {}},},
     *     @OA\Parameter(
     *         description="지정 파츠 이름",
     *         in="path",
     *         name="name",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         @OA\Examples(example="string", value="이름 1", summary="paramter"),
     *     ),
     *     @OA\RequestBody(
     *          @OA\JsonContent(
     *              @OA\Property(property="description", type="string", example="설명 1", description="설명"),
     *              @OA\Property(property="data_json", type="string", example="{}", description="{}")
     *         )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="결과값",
     *          @OA\JsonContent(
     *              @OA\Property(property="result_code", type="int", example="0", description="성공:0, 실패:-1"),
     *              @OA\Property(property="result_message", type="string", example="Success", description="성공:EMPTY, 실패:에러메세지(데이터 포맷 미 일치"),
     *              @OA\Property(property="result_data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="int", description="Id", example="Id"),
     *                      @OA\Property(property="name", type="string", description="이름", example="이름 1"),
     *                      @OA\Property(property="description", type="string", description="설명", example="설명 1"),
     *                      @OA\Property(property="data_json", type="string", description="JSON 문자열", example="{}"),
     *                  ),
     *             )
     *         )
     *     )
     * )
     */
    public function updateByName(Request $request, $name)
    {
        $validator = Validator::make($request->all(), [
            "description" => "required|max:512",
            "data_json" => "required",
        ]);

        if ($validator->fails()) {
            return response()->json([
                "result_code" => -1,
                "result_message" => $validator->errors(),
            ]);
        }

        if (!$this->isJson($request->data_json)) {
            return response()->json([
                "result_code" => -1,
                "result_message" => "'data_json' Invalid Json String Format",
            ]);
        }

        DB::beginTransaction();
        try {
            $part = Part::where("name", "=", $name)->first();
            if (empty($part)) {
                return response()->json([
                    "result_code" => -1,
                    "result_message" => "Part not found",
                ]);
            }

            $part->update($request->all());

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Database Update Fail: " . $e->getMessage());
            return response()->json([
                "result_code" => -1,
                "result_message" => "Database Update Fail",
            ]);
        }

        return response()->json([
            "result_code" => 0,
            "result_message" => "Success",
            "result_data" => [
                "id" => $part->id,
                "name" => $part->name,
                "description" => $part->description,
                "data_json" => $part->data_json,
            ],
        ]);
    }

    /**
     * @OA\Delete (
     *     path="/deleteByName/{name}",
     *     summary="",
     *     tags={"- 파츠 삭제(by name)"},
     *     description="지정한 파츠를 삭제한다, header.X-API-Key에 Api Key 필요",
     *     security={{"api-key": {}},},
     *     @OA\Parameter(
     *         description="지정 파츠 이름",
     *         in="path",
     *         name="name",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         @OA\Examples(example="string", value="이름 1", summary="paramter"),
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="결과값",
     *          @OA\JsonContent(
     *              @OA\Property(property="result_code", type="int", example="0", description="성공:0, 실패:-1"),
     *              @OA\Property(property="result_message", type="string", example="Success", description="성공:EMPTY, 실패:에러메세지(데이터 포맷 미 일치"),
     *              @OA\Property(property="result_data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="int", description="Id", example="Id"),
     *                      @OA\Property(property="name", type="string", description="이름", example="이름 1"),
     *                      @OA\Property(property="description", type="string", description="설명", example="설명 1"),
     *                      @OA\Property(property="data_json", type="string", description="JSON 문자열", example="{}"),
     *                  ),
     *             )
     *         )
     *     )
     * )
     */
    public function deleteByName(Request $request, $name)
    {
        DB::beginTransaction();
        try {
            $part = Part::where("name", "=", $name)->first();
            if (empty($part)) {
                return response()->json([
                    "result_code" => -1,
                    "result_message" => "Part not found",
                ]);
            }

            Part::where("name", "=", $name)->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Database Delete Fail: " . $e->getMessage());
            return response()->json([
                "result_code" => -1,
                "result_message" => "Database Delete Fail",
            ]);
        }

        return response()->json([
            "result_code" => 0,
            "result_message" => "Success",
            "result_data" => [
                "id" => $part->id,
                "name" => $part->name,
                "description" => $part->description,
                "data_json" => $part->data_json,
            ],
        ]);
    }

    // /**
    //  * @OA\Get (
    //  *     path="/list/{id}",
    //  *     summary="",
    //  *     tags={"- 지정 파츠 요청(by Id)"},
    //  *     description="지정 파츠를 요청 한다, header.X-API-Key에 Api Key 필요",
    //  *     security={{"api-key": {}},},
    //  *     @OA\Parameter(
    //  *         description="지정 파츠 id",
    //  *         in="path",
    //  *         name="id",
    //  *         required=true,
    //  *         @OA\Schema(type="integer"),
    //  *         @OA\Examples(example="integer", value=1, summary="paramter"),
    //  *     ),
    //  *     @OA\Response(
    //  *          response="200",
    //  *          description="결과값",
    //  *          @OA\JsonContent(
    //  *              @OA\Property(property="result_code", type="int", example="0", description="성공:0, 실패:-1"),
    //  *              @OA\Property(property="result_message", type="string", example="Success", description="성공:Success, 실패: 파츠가 존재 하지 않음"),
    //  *              @OA\Property(property="result_data", type="array",
    //  *                  @OA\Items(
    //  *                      @OA\Property(property="name", type="string", description="이름", example="이름 1"),
    //  *                      @OA\Property(property="description", type="string", description="설명", example="설명 1"),
    //  *                      @OA\Property(property="data_json", type="string", description="JSON 문자열", example="{}"),
    //  *                  ),
    //  *             )
    //  *         )
    //  *     )
    //  * )
    //  */
    public function show($id)
    {
        try {
            $part = Part::where("id", "=", $id)->first();
        } catch (\Exception $e) {
            Log::error("Database Query Fail: " . $e->getMessage());
            return response()->json([
                "result_code" => -1,
                "result_message" => "Database Query Fail",
            ]);
        }

        if (empty($part)) {
            return response()->json([
                "result_code" => -1,
                "result_message" => "Part not found",
            ]);
        }

        return response()->json([
            "result_code" => 0,
            "result_message" => "Success",
            "result_data" => [
                "name" => $part->name,
                "description" => $part->description,
                "data_json" => $part->data_json,
            ],
        ]);
    }

    // /**
    //  * @OA\Put (
    //  *     path="/update/{id}",
    //  *     summary="",
    //  *     tags={"- 파츠 수정(by Id)"},
    //  *     description="지정한 파츠를 수정한다, 지정 id와 이름, 설명, json data로 요청하면 갱신한다. header.X-API-Key에 Api Key 필요",
    //  *     security={{"api-key": {}},},
    //  *     @OA\Parameter(
    //  *         description="지정 파츠 이름",
    //  *         in="path",
    //  *         name="id",
    //  *         required=true,
    //  *         @OA\Schema(type="string"),
    //  *         @OA\Examples(example="string", value="a", summary="paramter"),
    //  *     ),
    //  *     @OA\RequestBody(
    //  *          @OA\JsonContent(
    //  *              @OA\Property(property="name", type="string", example="이름 1", description="이름"),
    //  *              @OA\Property(property="description", type="string", example="설명 1", description="설명"),
    //  *              @OA\Property(property="data_json", type="string", example="{}", description="{}")
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *          response="200",
    //  *          description="결과값",
    //  *          @OA\JsonContent(
    //  *              @OA\Property(property="result_code", type="int", example="0", description="성공:0, 실패:-1"),
    //  *              @OA\Property(property="result_message", type="string", example="Success", description="성공:EMPTY, 실패:에러메세지(데이터 포맷 미 일치"),
    //  *              @OA\Property(property="result_data", type="array",
    //  *                  @OA\Items(
    //  *                      @OA\Property(property="id", type="int", description="Id", example="Id"),
    //  *                      @OA\Property(property="name", type="string", description="이름", example="이름 1"),
    //  *                      @OA\Property(property="description", type="string", description="설명", example="설명 1"),
    //  *                      @OA\Property(property="data_json", type="string", description="JSON 문자열", example="{}"),
    //  *                  ),
    //  *             )
    //  *         )
    //  *     )
    //  * )
    //  */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            "description" => "required|max:512",
            "data_json" => "required",
        ]);

        if ($validator->fails()) {
            return response()->json([
                "result_code" => -1,
                "result_message" => $validator->errors(),
            ]);
        }

        if (!$this->isJson($request->data_json)) {
            return response()->json([
                "result_code" => -1,
                "result_message" => "'data_json' Invalid Json String Format",
            ]);
        }

        DB::beginTransaction();
        try {
            $part = Part::where("id", "=", $id)->first();
            if (empty($part)) {
                return response()->json([
                    "result_code" => -1,
                    "result_message" => "Part not found",
                ]);
            }

            $part->update($request->all());

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Database Update Fail: " . $e->getMessage());
            return response()->json([
                "result_code" => -1,
                "result_message" => "Database Update Fail",
            ]);
        }

        return response()->json([
            "result_code" => 0,
            "result_message" => "Success",
            "result_data" => [
                "id" => $part->id,
                "name" => $part->name,
                "description" => $part->description,
                "data_json" => $part->data_json,
            ],
        ]);
    }
}
