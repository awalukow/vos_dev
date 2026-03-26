{{-- resources/views/portal/sales/reports.blade.php --}}
@extends('portal.layouts.app')
@section('title', 'Sales Reports')
@section('page-title', 'Sales — Reports')
@section('content')
@include('portal.sales._wip', ['title' => 'Sales Reports', 'subtitle' => 'Our data analysts are still arguing about which chart type is "most impactful." Pie chart enthusiasts vs bar chart absolutists. It's tense.'])
@endsection
