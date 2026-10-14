<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pitch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PitchController extends Controller
{
    public function index(): View
    {
        return view('admin.pitches.index', [
            'pitches' => Pitch::query()->orderBy('id')->get(),
            'success' => session('success'),
            'error' => session('error'),
        ]);
    }

    public function show(int $id): View
    {
        return view('admin.pitches.show', [
            'pitch' => Pitch::query()->findOrFail($id),
        ]);
    }

    public function create(): View
    {
        return view('admin.pitches.create', [
            'old' => session()->get('_old_input', []),
            'error' => session('error') ?? session('errors')?->first(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $data['name'] = trim($data['name']);
        $data['description'] = trim($data['description'] ?? '');
        $data['image'] = $request->hasFile('image')
            ? 'storage/' . $request->file('image')->store('pitches', 'public')
            : null;

        Pitch::query()->create($data);

        return redirect('/admin/pitches')->with('success', 'Thêm sân thành công.');
    }

    public function edit(int $id): View
    {
        return view('admin.pitches.edit', [
            'pitch' => Pitch::query()->findOrFail($id),
            'old' => session()->get('_old_input', []),
            'error' => session('error') ?? session('errors')?->first(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $pitch = Pitch::query()->findOrFail($id);
        $data = $request->validate($this->rules());
        $data['name'] = trim($data['name']);
        $data['description'] = trim($data['description'] ?? '');

        if ($request->hasFile('image')) {
            $data['image'] = 'storage/' . $request->file('image')->store('pitches', 'public');
        } else {
            unset($data['image']);
        }

        $pitch->update($data);

        return redirect('/admin/pitches')->with('success', 'Cập nhật sân thành công.');
    }

    public function toggleStatus(int $id): RedirectResponse
    {
        $pitch = Pitch::query()->findOrFail($id);
        $pitch->update(['status' => $pitch->status === 'active' ? 'inactive' : 'active']);

        return redirect('/admin/pitches')->with('success', 'Đã cập nhật trạng thái sân.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $pitch = Pitch::query()->findOrFail($id);
        if ($pitch->bookings()->exists()) {
            return redirect('/admin/pitches')->with('error', 'Không thể xóa sân đã có đơn đặt.');
        }

        $pitch->delete();

        return redirect('/admin/pitches')->with('success', 'Xóa sân thành công.');
    }

    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'type' => ['required', 'integer', 'in:5,7,11'],
            'price_per_hour' => ['required', 'numeric', 'min:0'],
            'address' => ['nullable', 'string', 'max:255'],
            'opening_time' => ['required', 'date_format:H:i'],
            'closing_time' => ['required', 'date_format:H:i', 'after:opening_time'],
            'hotline' => ['nullable', 'string', 'max:30'],
            'pitch_size' => ['nullable', 'string', 'max:100'],
            'field_count' => ['required', 'integer', 'min:1'],
            'is_open' => ['required', 'boolean'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:active,inactive'],
            'image' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
