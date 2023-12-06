@extends('layouts.converter')

@section('content')
<div class="container">

    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">Currency converter <span class="float-end">
                        <div class="dropdown">
  <a class="btn btn-secondary dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">
     @if(session()->has('provider'))
          Your provider is: <span id="provider_name">{{ session()->get('provider') }}</span>
     @else
         Choose provider:
     @endif
  </a>

  <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink">
    @foreach($providers as $provider)
          <li  @if($provider->name === session()->get('provider')) class="d-none"@endif><a data-calculates_total="{{$provider->calculates_total}}"
                 data-conversion_endpoint_url="{{$provider->conversion_endpoint_url}}"
                 data-historical_endpoint_url="{{$provider->historical_endpoint_url}}"
                 data-provider_name="{{ $provider->name }}"
                 class="dropdown-item" href="#">{{ $provider->name }}</a>
          </li>
    @endforeach
  </ul>
</div>
                    </span></div>

                <div class="card-body bleu2">
                   @include('converter/form')
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
