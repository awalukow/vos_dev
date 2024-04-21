@extends('commonTemplate')

@section('content')

<div class="container-fluid bg-light">
    <div class="row justify-content-between align-items-center p-3">
        <div class="col-auto">
            <a href="/">
                <img src="{{asset('assets/images/logo-dark.png')}}" alt="VOS Logo" class="light" height="30px" width="43px">
            </a>
        </div>
        <div class="col-auto">
            <a href="/" class="text-uppercase">Home</a>
        </div>
    </div>
</div>

<div class="text-center mb-3">
    <img src="{{asset('assets/images/vos-logo.jpg')}}" class="logo" width="17%" height="auto">
    <h4 class="mb-4">
        Digital Signature Verification
        <span class="d-block translation font-italic text-muted mt-2" style="font-size: 70%;">Verifikasi Tanda Tangan Elektronik</span>
    </h4>
    <h5 class="mb-4">
        Voice of Soul Choir Indonesia states that:
        <span class="d-block translation font-italic text-muted mt-2" style="font-size: 70%;">Voice of Soul Choir Indonesia menyatakan bahwa:</span>
    </h5>
</div>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Document Verification</div>
                <div class="card-body">
                    <div>
                        <div class="row">
                            <div class="col">
                                <span>Document Name:</span>
                                <small class="d-block font-italic text-muted mb-2">Nama Dokumen:</small>
                            </div>
                            <div class="col text-right">
                                <p class="font-weight-bold mb-4 text-break">{{ $documentName }}</p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <span>Signed By:</span>
                                <small class="d-block font-italic text-muted mb-2">Ditandatangani Oleh:</small>
                            </div>
                            <div class="col text-right">
                                <p class="font-weight-bold mb-4 text-break">{{ $signedBy }}</p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <span>Validation Status:</span>
                                <small class="d-block font-italic text-muted mb-2">Status Validasi:</small>
                            </div>
                            <div class="col text-right">
                                @if($validationStatus == 'VALID')
                                <p class="font-weight-bold mb-4 text-break" style="color: green;">{{ $validationStatus }}</p>
                                @else
                                <p class="font-weight-bold mb-4 text-break" style="color: red;">{{ $validationStatus }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <span>Valid Thru:</span>
                                <small class="d-block font-italic text-muted mb-2">Berlaku Hingga:</small>
                            </div>
                            <div class="col text-right">
                                <p class="font-weight-bold mb-4 text-break">{{ $validThru }}</p>
                            </div>
                        </div>

                        @if($validationStatus == 'NOT VALID')
                        <div class="row">
                            <div class="col">
                                <span>Invalid Reason:</span>
                                <small class="d-block font-italic text-muted mb-2">Alasan Tidak Valid:</small>
                            </div>
                            <div class="col text-right">
                                <p class="font-weight-bold mb-4 text-break" style="color: red;">{{ $invalidReason }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
