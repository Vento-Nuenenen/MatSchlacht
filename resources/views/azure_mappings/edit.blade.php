@extends('layouts.app')

 @section('content')
     <div class="container">
         <h2>Azure Mapping anpassen</h2>
         <form method="POST" action="{{ route('azure-group-role-mappings.update', $azureGroupRoleMapping->id) }}">
             @method('PUT')
             @include('azure_mappings.form', [
                'buttonText' => 'Update',
                'azureGroupRoleMapping' => $azureGroupRoleMapping,
             ])
         </form>
     </div>
 @endsection
