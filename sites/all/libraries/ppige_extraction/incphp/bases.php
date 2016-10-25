<?php
/****************************************************************************************
  Copyright (c) 2005-2006 GEOSIGNAL - www.geosignal.fr - contact@geosignal.fr (J.Duclos)
****************************************************************************************/

session_start();

$base_carto = 'BD Carto®';
$base_teleatlas = '©Tele Atlas MultiNet';
$base_parcellaire = 'BD Parcellaire®';
$base_mnt = '©I2G Modèle Numérique de Terrain 2005';
$base_carthage = 'carthage';

$bases_EPF = array(
  'acces_equipement' => $base_carto,
  'aerodrome' => $base_carto,
  'arrondissement' => $base_carto,
  'bdg_cours_d_eau_polyline' => $base_carthage,
  'bdg_hydrographie_surfacique_region' => $base_carthage,
  'bdg_hydrographie_texture_region' => $base_carthage,
  'bdg_laisse_polyline' => $base_carthage,
  'bdg_noeud_hydrographique_point' => $base_carthage,
  'bdg_point_eau_isole_point' => $base_carthage,
  'bdg_region_hydrographique_region' => $base_carthage,
  'bdg_secteur_region' => $base_carthage,
  'bdg_sous_secteur_region' => $base_carthage,
  'bdg_troncon_hydrographique_point' => $base_carthage,
  'bdg_troncon_hydrographique_polyline' => $base_carthage,
  'bdg_zone_hydrographique_region' => $base_carthage,
  'bdp_batiment' => $base_parcellaire,
  'bdp_commune' => $base_parcellaire,
  'bdp_divcad' => $base_parcellaire,
  'bdp_localisant' => $base_parcellaire,
  'bdp_parcelle' => $base_parcellaire,
  'canton' => $base_carto,
  'cimetiere' => $base_carto,
  'commune' => $base_carto,
  'communication_restreinte' => $base_carto,
  'construction_elevee' => $base_carto,
  'contour_rurale_polyline' => '',
  'contour_rurale_region' => '',
  'debut_section' => $base_carto,
  'departement' => $base_carto,
  'digue' => $base_carto,
  'enceinte_militaire' => $base_carto,
  'equipement_routier' => $base_carto,
  'etablissement' => $base_carto,
  'franchissement' => $base_carto,
  'geometry_columns' => '',
  'grid_rurale_point' => '',
  'hydro' => $base_carto,
  'itineraire' => $base_carto,
  'laisse' => $base_carto,
  'liaison_maritime' => $base_carto,
  'ligne_electrique' => $base_carto,
  'limite_administrative' => $base_carto,
  'massif_boise' => $base_carto,
  'menu' => '',
  'mnt_courbe_maitresse_point' => $base_mnt,
  'mnt_courbe_maitresse_polyline' => $base_mnt,
  'mnt_courbe_maitresse_region' => $base_mnt,
  'mnt_courbe_normale_point' => $base_mnt,
  'mnt_courbe_normale_polyline' => $base_mnt,
  'mnt_courbe_normale_region' => $base_mnt,
  'mnt_dalles_region' => $base_mnt,
  'noeud_ferre' => $base_carto,
  'noeud_routier' => $base_carto,
  'piste_eorodrome' => $base_carto,
  'point_remarquable_relief' => $base_carto,
  'ponctuel' => $base_carto,
  'projection' => '',
  'projet_contact' => '',
  'projet_contact_libelle' => '',
  'projet_organisme' => '',
  'projet_organisme_statut' => '',
  'projet_organisme_type' => '',
  'projet_organisme_type_licencie' => '',
  'projet_projection_type' => '',
  'projet_sondage' => '',
  'projet_sondage_q1' => '',
  'projet_sondage_q2' => '',
  'projet_stat_compteur' => '',
  'projet_stat_inscription' => '',
  'projet_stat_telechargement' => '',
  'projet_statut' => '',
  'projet_ville_associee' => '',
  'projet_ville_groupe' => '',
  'reg31_00_polyline' => $base_teleatlas,
  'reg31_01_polyline' => $base_teleatlas,
  'reg31_02_polyline' => $base_teleatlas,
  'reg31_03_polyline' => $base_teleatlas,
  'reg31_04_polyline' => $base_teleatlas,
  'reg31_05_polyline' => $base_teleatlas,
  'reg31_06_polyline' => $base_teleatlas,
  'reg31_07_polyline' => $base_teleatlas,
  'reg31_08_polyline' => $base_teleatlas,
  'reg31_2r_none' => $base_teleatlas,
  'reg31_a0_region' => $base_teleatlas,
  'reg31_a1_region' => $base_teleatlas,
  'reg31_a7_region' => $base_teleatlas,
  'reg31_a8_region' => $base_teleatlas,
  'reg31_aa_region' => $base_teleatlas,
  'reg31_aanm_none' => $base_teleatlas,
  'reg31_ab_none' => $base_teleatlas,
  'reg31_as_region' => $base_teleatlas,
  'reg31_ba_none' => $base_teleatlas,
  'reg31_be_none' => $base_teleatlas,
  'reg31_bn_none' => $base_teleatlas,
  'reg31_bu_region' => $base_teleatlas,
  'reg31_cf_point' => $base_teleatlas,
  'reg31_cn_none' => $base_teleatlas,
  'reg31_fe_polyline' => $base_teleatlas,
  'reg31_gc_polyline' => $base_teleatlas,
  'reg31_ig_none' => $base_teleatlas,
  'reg31_is_none' => $base_teleatlas,
  'reg31_jc_point' => $base_teleatlas,
  'reg31_lc_region' => $base_teleatlas,
  'reg31_ls_polyline' => $base_teleatlas,
  'reg31_lu_region' => $base_teleatlas,
  'reg31_lxnm_none' => $base_teleatlas,
  'reg31_mn_point' => $base_teleatlas,
  'reg31_mp_none' => $base_teleatlas,
  'reg31_nw_polyline' => $base_teleatlas,
  'reg31_nwea_none' => $base_teleatlas,
  'reg31_oa07_region' => $base_teleatlas,
  'reg31_oanm_none' => $base_teleatlas,
  'reg31_ol_none' => $base_teleatlas,
  'reg31_pc_none' => $base_teleatlas,
  'reg31_pd_region' => $base_teleatlas,
  'reg31_pe_none' => $base_teleatlas,
  'reg31_pi_point' => $base_teleatlas,
  'reg31_piea_none' => $base_teleatlas,
  'reg31_pinm_none' => $base_teleatlas,
  'reg31_pr_none' => $base_teleatlas,
  'reg31_ps_point' => $base_teleatlas,
  'reg31_rd_none' => $base_teleatlas,
  'reg31_rn_none' => $base_teleatlas,
  'reg31_rr_polyline' => $base_teleatlas,
  'reg31_rrea_none' => $base_teleatlas,
  'reg31_rrnm_none' => $base_teleatlas,
  'reg31_rs_none' => $base_teleatlas,
  'reg31_sc_none' => $base_teleatlas,
  'reg31_se_none' => $base_teleatlas,
  'reg31_sg_point' => $base_teleatlas,
  'reg31_si_none' => $base_teleatlas,
  'reg31_sm_point' => $base_teleatlas,
  'reg31_smea_none' => $base_teleatlas,
  'reg31_smnm_none' => $base_teleatlas,
  'reg31_sp_none' => $base_teleatlas,
  'reg31_sr_none' => $base_teleatlas,
  'reg31_st_none' => $base_teleatlas,
  'reg31_sxnm_none' => $base_teleatlas,
  'reg31_ta_none' => $base_teleatlas,
  'reg31_tc_none' => $base_teleatlas,
  'reg31_td_none' => $base_teleatlas,
  'reg31_tg_none' => $base_teleatlas,
  'reg31_tl_none' => $base_teleatlas,
  'reg31_to_none' => $base_teleatlas,
  'reg31_tp_none' => $base_teleatlas,
  'reg31_vr_none' => $base_teleatlas,
  'reg31_wa_region' => $base_teleatlas,
  'reg31_wl_polyline' => $base_teleatlas,
  'reg31_wxnm_none' => $base_teleatlas,
  'spatial_ref_sys' => '',
  'test_jds' => '',
  'thematique' => '',
  'toponyme_surface_hydrographique' => $base_carto,
  'troncon' => $base_carto,
  'troncon_route' => $base_carto,
  'troncon_voie_ferree' => $base_carto,
  'zone_activite' => $base_carto,
  'zone_habitat' => $base_carto,
  'zone_hydrographique_texture' => $base_carto,
  'zone_occupation_sol' => $base_carto,
  'zone_reglemente_touristique' => $base_carto
);

$_SESSION['bases_EPF'] = $bases_EPF;

//******************************************************************************

// Ne pas modifier ces valeurs 
// nécessaire pour faire la correspondance entre la table rastertank "datasets.product_type" et le viewer
// et pour les métadonnées epf/projetepf/viewer/data/metadata : 
//    epf/projetepf/viewer/data/metadata/Scan100.txt
//    epf/projetepf/viewer/data/metadata/Scan25.txt
//    ...
$bdParcellaire = 'bdparcellaire';
$orthophoto = 'Orthophoto';
$scan100 = 'Scan100';
$scan25 = 'Scan25';
$scan25EDR = 'Scan25 EDR';
$scanReg = 'ScanReg';
// Fin de l'interdiction

// Ajout de document(s) aux bases
$docs_EPF = array(
  $base_carto => array(
    'DL_BDCARTO_gc.pdf',
    'DL_BDCARTO_mif.pdf',
    'DL_BDCARTO_shp.pdf'
  ),
  $base_teleatlas => array(
    'MultiNet ShapeFile - Modele Relationnel.pdf',
    'TeleAtlas Multinet - Contenu des tables - 297_650.pdf',
    'TeleAtlas Multinet - Contenu des tables - A4 Paysage.pdf',
    'TeleAtlas MultiNet - Presentation de la base de donnees.pdf',
    'TeleAtlas Multinet - Specification du format ShapeFile 4.3.1 - FRANCAIS.pdf',
    'TeleAtlas MultiNet_Shapefile_4-3-1_Version_Final_v1-0.pdf',
    'TeleAtlas MultiNet_Shapefile_RelationalModel.pdf'
  ),
  $scan100 => array(
    'LEGENDE_SCAN100.PDF',
    'TA_SCAN100.PDF',
    'TA_SCAN100_L93.PDF'
  ),
  $scan25 => array(
    'DC_DL_SCAN25.PDF'
  ),
  $scan25EDR => array(
    'DC_DL_SCAN25_EDR.PDF'
  ),
  $scanReg => array(
    'DC_DL_SCANREG_2.PDF',
    'SCANREG_2005_LEG.TIF',
    'TA_SCANREG.PDF',
    'TA_SCANREG_L93.PDF'
  )
);

$_SESSION['docs_EPF'] = $docs_EPF;

// Gestion des tables attributaires
$tbl_attr_EPF = array(
/* Exemple fictif pour la couche département */
/*
  'departement' => array(
    array('departement.id_bdcarto', 'commune.id_bdcarto'),
    array('commune.id_bdcarto', 'cimetiere.id_bdcarto')
  )
*/
// Commencer ci-dessous  

);

$_SESSION['tbl_attr_EPF'] = $tbl_attr_EPF;

?>
