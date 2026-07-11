import ManajemenLayout from '@/Layouts/ManajemenLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { KeyRound, Check, ShieldCheck, Plus, Trash2, X } from 'lucide-react';
import { useState } from 'react';

/* Kotak centang kecil ala tema */
function Box({ checked, disabled = false }) {
    return (
        <span className={`inline-flex items-center justify-center w-5 h-5 rounded-md border transition-colors ${
            checked ? 'bg-[#A9791F] border-[#A9791F] text-white' : 'border-gray-300 bg-white'
        } ${disabled ? 'opacity-50' : ''}`}>
            {checked && <Check size={13} strokeWidth={3} />}
        </span>
    );
}

function UserRow({ user, moduleKeys, moduleList }) {
    const [sel, setSel] = useState(Array.isArray(user.modules) ? user.modules : []);
    const [saving, setSaving] = useState(false);
    const [dirty, setDirty] = useState(false);
    const full = sel.includes('*');

    const toggleFull = () => { setSel(full ? [] : ['*']); setDirty(true); };
    const toggleMod = (key) => {
        if (full) return;
        setSel((prev) => (prev.includes(key) ? prev.filter((k) => k !== key) : [...prev, key]));
        setDirty(true);
    };

    const save = () => {
        setSaving(true);
        router.patch(route('manajemen.officeusers.update', user.id), { modules: sel }, {
            preserveScroll: true, preserveState: true,
            onSuccess: () => { setSaving(false); setDirty(false); },
            onError: () => setSaving(false),
        });
    };

    const hapus = () => {
        if (!confirm(`Hapus user "${user.name}"? Akun login-nya akan hilang.`)) return;
        router.delete(route('manajemen.officeusers.destroy', user.id), { preserveScroll: true });
    };

    return (
        <tr className="border-b border-gray-100 hover:bg-[#FBF8F0]/60">
            <td className="sticky left-0 z-10 px-3 py-2.5 bg-white">
                <p className="text-sm font-bold text-gray-900 whitespace-nowrap">{user.name}</p>
                <p className="text-[11px] text-gray-400 whitespace-nowrap">{user.role || '—'}{user.active ? '' : ' · nonaktif'}</p>
            </td>

            <td className="px-2 py-2.5 text-center">
                <button type="button" onClick={toggleFull} title="Akses penuh (semua modul)"><Box checked={full} /></button>
            </td>

            {moduleKeys.map((key) => (
                <td key={key} className="px-2 py-2.5 text-center">
                    <button type="button" onClick={() => toggleMod(key)} disabled={full} title={moduleList[key]}>
                        <Box checked={full || sel.includes(key)} disabled={full} />
                    </button>
                </td>
            ))}

            <td className="sticky right-0 z-10 px-3 py-2.5 bg-white">
                <div className="flex items-center justify-end gap-1.5">
                    <button onClick={save} disabled={saving || !dirty}
                        className="px-3 py-1 text-xs font-bold text-white bg-[#A9791F] rounded-lg hover:bg-[#7A560F] disabled:opacity-40">
                        {saving ? '...' : 'Simpan'}
                    </button>
                    <button onClick={hapus} title="Hapus user"
                        className="p-1.5 text-red-400 rounded-lg hover:text-red-600 hover:bg-red-50">
                        <Trash2 size={14} />
                    </button>
                </div>
            </td>
        </tr>
    );
}

function AddUserModal({ moduleKeys, moduleList, onClose }) {
    const [name, setName] = useState('');
    const [pin, setPin] = useState('');
    const [keterangan, setKeterangan] = useState('');
    const [active, setActive] = useState(true);
    const [sel, setSel] = useState([]);
    const [saving, setSaving] = useState(false);
    const [err, setErr] = useState('');
    const full = sel.includes('*');

    const toggleFull = () => setSel(full ? [] : ['*']);
    const toggleMod = (key) => {
        if (full) return;
        setSel((prev) => (prev.includes(key) ? prev.filter((k) => k !== key) : [...prev, key]));
    };

    const submit = () => {
        setErr('');
        if (!name.trim()) return setErr('Nama wajib diisi.');
        if (!/^\d{3,8}$/.test(pin.trim())) return setErr('PIN harus 3–8 digit angka.');
        setSaving(true);
        router.post(route('manajemen.officeusers.store'), {
            name: name.trim(), pin: pin.trim(), keterangan: keterangan.trim(), active, modules: sel,
        }, {
            preserveScroll: true,
            onSuccess: () => { setSaving(false); onClose(); },
            onError: () => setSaving(false),
        });
    };

    return (
        <div className="fixed inset-0 z-50 flex items-start justify-center p-4 overflow-y-auto bg-black/40 backdrop-blur-sm">
            <div className="w-full max-w-lg my-8 bg-white shadow-xl rounded-2xl">
                <div className="flex items-center justify-between p-5 border-b border-gray-100">
                    <div className="flex items-center gap-3">
                        <div className="flex items-center justify-center w-10 h-10 rounded-xl bg-[#F2E9D3] text-[#A9791F]"><Plus size={18} /></div>
                        <h2 className="text-base font-extrabold text-gray-900">Tambah User</h2>
                    </div>
                    <button onClick={onClose} className="p-1.5 text-gray-400 rounded-lg hover:bg-gray-100"><X size={18} /></button>
                </div>

                <div className="p-5 space-y-4">
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="block mb-1 text-xs font-bold text-gray-600">Nama</label>
                            <input value={name} onChange={(e) => setName(e.target.value)} placeholder="cth: Budi"
                                className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-[#A9791F] focus:border-[#A9791F]" />
                        </div>
                        <div>
                            <label className="block mb-1 text-xs font-bold text-gray-600">PIN (3–8 digit)</label>
                            <input value={pin} onChange={(e) => setPin(e.target.value)} inputMode="numeric" placeholder="cth: 1234"
                                className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-[#A9791F] focus:border-[#A9791F]" />
                        </div>
                    </div>
                    <div>
                        <label className="block mb-1 text-xs font-bold text-gray-600">Keterangan (opsional)</label>
                        <input value={keterangan} onChange={(e) => setKeterangan(e.target.value)}
                            className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-[#A9791F] focus:border-[#A9791F]" />
                    </div>

                    <div>
                        <p className="mb-2 text-xs font-bold text-gray-600">Akses Modul</p>
                        <button type="button" onClick={toggleFull}
                            className={`flex items-center justify-between w-full px-3 py-2 mb-2 border rounded-lg ${full ? 'border-[#A9791F] bg-[#FBF8F0]' : 'border-gray-200'}`}>
                            <span className="inline-flex items-center gap-1.5 text-sm font-bold text-gray-800"><ShieldCheck size={14} /> Semua (akses penuh)</span>
                            <Box checked={full} />
                        </button>
                        <div className="grid grid-cols-2 gap-2">
                            {moduleKeys.map((key) => (
                                <button key={key} type="button" onClick={() => toggleMod(key)} disabled={full}
                                    className={`flex items-center justify-between px-3 py-2 text-left border rounded-lg transition-colors ${
                                        (full || sel.includes(key)) ? 'border-[#A9791F] bg-[#FBF8F0]' : 'border-gray-200 hover:bg-gray-50'
                                    } ${full ? 'opacity-60' : ''}`}>
                                    <span className="text-xs font-semibold text-gray-800">{moduleList[key]}</span>
                                    <Box checked={full || sel.includes(key)} disabled={full} />
                                </button>
                            ))}
                        </div>
                    </div>

                    <label className="inline-flex items-center gap-2 text-sm font-semibold text-gray-700">
                        <input type="checkbox" checked={active} onChange={(e) => setActive(e.target.checked)} className="w-4 h-4 accent-[#A9791F]" />
                        Akun aktif
                    </label>

                    {err && <p className="text-xs font-bold text-red-600">{err}</p>}
                </div>

                <div className="flex justify-end gap-3 px-5 py-4 border-t border-gray-100 bg-gray-50">
                    <button onClick={onClose} className="px-5 py-2 text-sm font-bold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50">Batal</button>
                    <button onClick={submit} disabled={saving}
                        className="px-5 py-2 text-sm font-bold text-white bg-[#A9791F] rounded-xl hover:bg-[#7A560F] disabled:opacity-60">
                        {saving ? 'Menyimpan...' : 'Tambah'}
                    </button>
                </div>
            </div>
        </div>
    );
}

export default function OfficeUsersIndex({ users, moduleList }) {
    const { flash } = usePage().props;
    const moduleKeys = Object.keys(moduleList);
    const [addOpen, setAddOpen] = useState(false);

    return (
        <ManajemenLayout>
            <Head title="Akses Modul - Laksamana Muda" />

            <div className="flex flex-wrap items-start justify-between gap-3 mb-6">
                <div>
                    <h1 className="flex items-center gap-2 text-3xl font-extrabold tracking-tight text-gray-900">
                        <KeyRound className="text-[#A9791F]" /> Akses Modul
                    </h1>
                    <p className="mt-1 text-sm text-gray-500">
                        Roster seluruh user kantor + akses modulnya. Kolom <b>Semua</b> = akses penuh.
                    </p>
                </div>
                <button onClick={() => setAddOpen(true)}
                    className="flex items-center gap-2 px-5 py-2 bg-[#A9791F] text-white text-sm font-bold rounded-xl hover:bg-[#7A560F] shadow-md shadow-[#A9791F]/20">
                    <Plus size={16} strokeWidth={3} /> Tambah User
                </button>
            </div>

            {flash?.success && (
                <div className="p-3 mb-5 text-sm font-bold text-green-700 border border-green-200 bg-green-50 rounded-xl">✅ {flash.success}</div>
            )}
            {flash?.error && (
                <div className="p-3 mb-5 text-sm font-bold text-red-700 border border-red-200 bg-red-50 rounded-xl">⚠️ {flash.error}</div>
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
                                <th key={key} className="px-2 py-3 text-[10px] font-semibold text-gray-500 whitespace-nowrap">{moduleList[key]}</th>
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
                Total {users.length} user. Perubahan berlaku saat user login berikutnya di portal.
            </p>

            {addOpen && <AddUserModal moduleKeys={moduleKeys} moduleList={moduleList} onClose={() => setAddOpen(false)} />}
        </ManajemenLayout>
    );
}
