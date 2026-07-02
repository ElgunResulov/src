<?php
include('db.php');
?>



            <div class="stats-grid mb-0">
                <div class="card">
                    <div class="stat-card">
                        <div class="stat-icon primary">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h2 class="stat-value"><?php ?></h2>
                            <div class="stat-label">Tələbə</div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="stat-card">
                        <div class="stat-icon success">
                            <i class="fas fa-user-friends"></i>
                        </div>
                        <div class="stat-info">
                            <h2 class="stat-value"><?php ?></h2>
                            <div class="stat-label">Qrup</div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="stat-card">
                        <div class="stat-icon info">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-info">
                            <h2 class="stat-value"><?php ?></h2>
                            <div class="stat-label">Mövzu</div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="stat-card">
                        <div class="stat-icon warning">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <div class="stat-info">
                            <h2 class="stat-value"><?php ?></h2>
                            <div class="stat-label">İmtahan</div>
                        </div>
                    </div>
                </div>
            </div>
    
                <div class="m-3 d-flexbox mb-0">
                    <div class="row">
                            <div class="card mb-20">
                                <div class="card-header">
                                    <h3 class="card-title">Yaxın İmtahanlar</h3>
                                    <a href="#" class="btn btn-sm btn-primary" onclick="showSection('exams')">Bütün İmtahanlar</a>
                                </div>
                                <div class="card-body">
                                    <div class="table-container">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>İmtahan</th>
                                                    <th>Fənn</th>
                                                    <th>Tarix</th>
                                                    <th>Müddət</th>
                                                    <th>Qruplar</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody >

                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mb-20">
                                <div class="card-header">
                                    <h3 class="card-title">Son Tapşırıqlar</h3>
                                    <a href="#" class="btn btn-sm btn-primary" onclick="showSection('assignments')">Bütün Tapşırıqlar</a>
                                </div>
                                <div class="card-body">
                                    <div class="table-container">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Tapşırıq</th>
                                                    <th>Mövzu</th>
                                                    <th>Qrup</th>
                                                    <th>Son Tarix</th>
                                                </tr>
                                            </thead>
                                            <tbody >
                                        
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>