            <!-- Umum Section -->
            <div class="px-4 mb-2">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Umum</span>
            </div>
            <a @click="setActiveTab('general')" 
               :class="{ 'active': activeTab === 'general' }" 
               class="sidebar-item flex items-center px-4 py-2.5 mx-2 rounded-lg cursor-pointer text-sm">
                <i class="fas fa-home w-5 mr-3 text-gray-400"></i>
                <span>Umum</span>
            </a>
            <a @click="setActiveTab('company')" 
               :class="{ 'active': activeTab === 'company' }" 
               class="sidebar-item flex items-center px-4 py-2.5 mx-2 rounded-lg cursor-pointer text-sm">
                <i class="fas fa-building w-5 mr-3 text-gray-400"></i>
                <span>Informasi Perusahaan</span>
            </a>
            
            <!-- Organisasi Section -->
            <div class="px-4 mt-4 mb-2">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Organisasi</span>
            </div>
            <a @click="setActiveTab('departments')" 
               :class="{ 'active': activeTab === 'departments' }" 
               class="sidebar-item flex items-center px-4 py-2.5 mx-2 rounded-lg cursor-pointer text-sm">
                <i class="fas fa-sitemap w-5 mr-3 text-gray-400"></i>
                <span>Departemen</span>
            </a>
            <a @click="setActiveTab('divisions')" 
               :class="{ 'active': activeTab === 'divisions' }" 
               class="sidebar-item flex items-center px-4 py-2.5 mx-2 rounded-lg cursor-pointer text-sm">
                <i class="fas fa-layer-group w-5 mr-3 text-gray-400"></i>
                <span>Divisi</span>
            </a>
            <a @click="setActiveTab('positions')" 
               :class="{ 'active': activeTab === 'positions' }" 
               class="sidebar-item flex items-center px-4 py-2.5 mx-2 rounded-lg cursor-pointer text-sm">
                <i class="fas fa-user-tie w-5 mr-3 text-gray-400"></i>
                <span>Posisi / Jabatan</span>
            </a>
            
            <!-- Karyawan Section -->
            <div class="px-4 mt-4 mb-2">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Karyawan</span>
            </div>
            <a @click="setActiveTab('employee-types')" 
               :class="{ 'active': activeTab === 'employee-types' }" 
               class="sidebar-item flex items-center px-4 py-2.5 mx-2 rounded-lg cursor-pointer text-sm">
                <i class="fas fa-id-card w-5 mr-3 text-gray-400"></i>
                <span>Status Karyawan</span>
            </a>
            <a @click="setActiveTab('locations')" 
               :class="{ 'active': activeTab === 'locations' }" 
               class="sidebar-item flex items-center px-4 py-2.5 mx-2 rounded-lg cursor-pointer text-sm">
                <i class="fas fa-map-marker-alt w-5 mr-3 text-gray-400"></i>
                <span>Lokasi Penempatan</span>
            </a>
