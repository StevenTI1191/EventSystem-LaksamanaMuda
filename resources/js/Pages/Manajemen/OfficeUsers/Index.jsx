import ManajemenLayout from '@/Layouts/ManajemenLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { KeyRound, Check, ShieldCheck } from 'lucide-react';
import { useState } from 'react';

function UserRow({ user, moduleKeys, moduleList }) {
    const initial = Array.isArray(user.modules) ? user.modules : [];
    const [sel, setSel] = useState(initial);
    const [saving, setSaving] = useState(false);
    const [dirty, setDirty] = useState(false);

    const full = sel.includes('*');

    const toggleFull = () => {
        setSel(full ? [] : ['*']);
        setDirty(true);
    };
    const toggleMod = (key) => {
        if (full) return;
        setSel((prev) => (prev.includes(key) ? prev.filter((k) => k !== key) : [...prev, key]));
        setDirty(true);
    };

    const save = () => {
        setSaving(true);
        router.patch(route('manajemen.officeusers.update', user.id), { modules: sel }, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => { setSaving(false); setDirty(false); },
            onError: () => setSaving(false),
        });
    };

    const box = (checked, disabled = false) => (
        <span className={`inline-flex items-center justify-center w-5 h-5 rounded-md border transition-colors ${
            checked ? 'bg-[#A9791F] border-[#A9791F] text-white' : 'border-gray-300 bg-white'
        } ${disabled ? 'opacity-50' : ''}`}>
            {checked && <Check size={13} strokeWidth={3} />}
        </span>
    );

    return (
        <tr className="border-b border-gray-100 hover:bg-[#FBF8F0]/60">
            <td className="sticky left-0 z-10 px-3 py-2.5 bg-white">
                <p className="text-sm font-bold text-gray-900 whitespace-nowrap">{user.name}</p>
                <p className="text-[11px] text-gray-400 whitespace-nowrap">{user.role || '—'}{user.active ? '' : ' · nonaktif'}</p>
            </td>

            {/* Full access */}
            <td className="px-2 py-2.5 text-center">
                <button type="button" onClick={toggleFull} title="Akses penuh (semua modul)">
                    {box(full)}
                </button>
            </td>

            {/* Tiap modul */}
            {moduleKeys.map((key) => {
                const checked = full || sel.includes(key);
                return (
                    <td key={key} className="px-2 py-2.5 text-center">
                        <button type="button" onClick={() => toggleMod(key)} disabled={full} title={moduleList[key]}>
                            {box(checked, full)}
                        </button>
                    </td>
                );
            })}

            <td className="sticky right-0 z-10 px-3 py-2.5 bg-white text-right">
                <button
                    onClick={save}
                    disabled={saving || !dirty}
                    className="px-3 py-1 text-xs font-bold text-white bg-[#A9791F] rounded-lg hover:bg-[#7A560F] disabled:opacity-40"
                >
                    {saving ? '...' : 'Simpan'}
                </button>
            </td>
        </tr>
    );
}

export default function OfficeUsersIndex({ users, moduleList }) {
    const { flash } = usePage().props;
    const moduleKeys = Object.keys(moduleList);

    return (
        <ManajemenLayout>
            <Head title="Akses Modul - Laksamana Muda" />

            <div className="mb-6">
                <h1 className="flex items-center gap-2 text-3xl font-extrabold tracking-tight text-gray-900">
                    <KeyRound className="text-[#A9791F]" /> Akses Modul
                </h1>
                <p className="mt-1 text-sm text-gray-500">
                    Roster seluruh user kantor. Centang modul yang boleh dibuka tiap orang, lalu Simpan.
                    Kolom <b>Semua</b> = akses penuh (admin).
                </p>
            </div>

            {flash?.success && (
                <div className="p-3 mb-5 text-sm font-bold text-green-700 border border-green-200 bg-green-50 rounded-xl">
                    ✅ {flash.success}
                </div>
            )}

            <div className="overflow-x-auto bg-white border border-gray-100 shadow-sm rounded-2xl">
                <table className="min-w-full text-sm border-collapse">
                    <thead>
                        <tr className="border-b border-gray-200 bg-gray-50">
                            <th className="sticky left-0 z-10 px-3 py-3 text-left bg-gray-50 text-[11px] font-bold tracking-wider text-gray-500 uppercase">Nama</th>
                            <th className="px-2 py-3 text-[10px] font-bold tracking-wide text-gray-500 uppercase">
                                <span className="inline-flex items-center gap-1"><ShieldCheck size={12} /> Semua</span>
                            </th>
                            {moduleKeys.map((key) => (
                                <th key={key} className="px-2 py-3 text-[10px] font-semibold text-gray-500 whitespace-nowrap">
                                    {moduleList[key]}
                                </th>
                            ))}
                            <th className="sticky right-0 z-10 px-3 py-3 bg-gray-50"></th>
                        </tr>
                    </thead>
                    <tbody>
                        {users.map((u) => (
                            <UserRow key={u.id} user={u} moduleKeys={moduleKeys} moduleList={moduleList} />
                        ))}
                    </tbody>
                </table>
            </div>

            <p className="mt-4 text-xs text-gray-400">
                Total {users.length} user. Perubahan langsung berlaku saat user login berikutnya di portal.
            </p>
        </ManajemenLayout>
    );
}
