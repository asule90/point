<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Model\Master\User;
use Illuminate\Http\Request;
use App\Model\Form;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCollection;
use Illuminate\Support\Facades\Artisan;
use App\Http\Resources\Project\Project\ProjectResource;
use App\Http\Requests\Project\Project\StoreProjectRequest;
use App\Http\Requests\Project\Project\DeleteProjectRequest;
use App\Http\Requests\Project\Project\UpdateProjectRequest;

class ReceiveItemController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return ApiCollection
     */
    public function index(Request $request)
    {
        $transfers = Form::select('forms.id', 'forms.date', 'forms.number', 'forms.approved', 'forms.canceled', 'forms.done')
            ->where('formable_type', 'receive');
        // dd($transfers->toSql());

        $transfers = pagination($transfers, $request->input('limit'));

        return new ApiCollection($transfers);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreProjectRequest $request
     * @return \App\Http\Resources\Project\Project\ProjectResource
     */
    public function store(StoreProjectRequest $request)
    {
        // User only allowed to create max 1 project
        $numberOfProject = Project::where('owner_id', auth()->user()->id)->count();
        // TODO: disable new project creation
        if ($numberOfProject >= 1) {
            return response()->json([
                'code' => 422,
                'message' => 'We are updating our server, currently you cannot create new project',
            ], 422);
        }

        // Create new database for tenant project
        $dbName = 'point_'.strtolower($request->get('code'));
        Artisan::call('tenant:database:create', ['db_name' => $dbName]);

        // Update tenant database name in configuration
        config()->set('database.connections.tenant.database', $dbName);
        DB::connection('tenant')->reconnect();
        DB::connection('tenant')->beginTransaction();

        $project = new Project;
        $project->owner_id = auth()->user()->id;
        $project->code = $request->get('code');
        $project->name = $request->get('name');
        $project->timezone = $request->header('timezone');
        $project->address = $request->get('address');
        $project->phone = $request->get('phone');
        $project->vat_id_number = $request->get('vat_id_number');
        $project->invitation_code = get_invitation_code();
        $project->save();

        $projectUser = new ProjectUser;
        $projectUser->project_id = $project->id;
        $projectUser->user_id = $project->owner_id;
        $projectUser->user_name = $project->owner->name;
        $projectUser->user_email = $project->owner->email;
        $projectUser->joined = true;
        $projectUser->save();

        // Migrate database
        Artisan::call('tenant:migrate', ['db_name' => $dbName]);

        // Clone user point into their database
        $user = new User;
        $user->id = auth()->user()->id;
        $user->name = auth()->user()->name;
        $user->first_name = auth()->user()->first_name;
        $user->last_name = auth()->user()->last_name;
        $user->email = auth()->user()->email;
        $user->save();

        Artisan::call('tenant:seed:first', ['db_name' => $dbName]);

        DB::connection('tenant')->commit();

        return new ProjectResource($project);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     *
     * @return \App\Http\Resources\Project\Project\ProjectResource
     */
    public function show($id)
    {
        return new ProjectResource(Project::findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\Project\Project\UpdateProjectRequest $request
     * @param  int                                                    $id
     *
     * @return \App\Http\Resources\Project\Project\ProjectResource
     */
    public function update(UpdateProjectRequest $request, $id)
    {
        // Update tenant database name in configuration
        $project = Project::findOrFail($id);
        $project->name = $request->get('name');
        $project->address = $request->get('address');
        $project->phone = $request->get('phone');
        $project->vat_id_number = $request->get('vat_id_number');
        $project->invitation_code = $request->get('invitation_code');
        $project->invitation_code_enabled = $request->get('invitation_code_enabled');
        $project->save();

        return new ProjectResource($project);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Http\Requests\Project\Project\DeleteProjectRequest $request
     * @param  int                                                    $id
     *
     * @return \App\Http\Resources\Project\Project\ProjectResource
     */
    public function destroy(DeleteProjectRequest $request, $id)
    {
        $project = Project::findOrFail($id);

        $project->delete();

        // Delete database tenant
        Artisan::call('tenant:database:delete', [
            'db_name' => 'point_'.strtolower($project->code),
        ]);

        return new ProjectResource($project);
    }
}