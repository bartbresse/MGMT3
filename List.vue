<template>
    <div>
        <div style="float: right;">
            <a :href="urlcsv">
                <el-button size="mini" type="success">Exporteer in CSV</el-button>
            </a>
            <router-link :to="'/add/'+entity">
                <el-button size="mini" type="primary">{{ entity }} toevoegen</el-button>
            </router-link>
        </div>
        <br/>
        <div class="searchfield">
            <el-input placeholder="Zoeken" v-on:change="searchnow" v-model="search" class="input-with-select">
                <el-button slot="append" icon="el-icon-search"></el-button>
            </el-input>
        </div>
        <el-table v-if="Array.isArray(data) && data.length > 0" ref="multipleTable"
                  style="width: 90vw;"
                  :data="data"
                  selected>
            <el-table-column :label="table['columns'][0]" fixed
                             :width="table['columnData'][table['columns'][0]].attr.width"
                             :min-width="table['columnData'][table['columns'][0]].attr.minWidth"
                             :prop="table['columns'][0]"
                             sortable>
                <template slot-scope="scope"><a :href="scope.row.viewuri">{{ scope.row[table['columns'][0]].value }}</a></template>
            </el-table-column>
            <el-table-column v-for="(column,index) in table['columns']" v-if="index > 0"
                             :label="column"
                             :min-width="table['columnData'][column].attr.minWidth"
                             :width="table['columnData'][column].attr.width"
                             :prop="column"
                             sortable>
                <template slot-scope="scope">
                    <a v-if="scope.row[column] && scope.row[column].url" :href="scope.row[column].url">{{
                        scope.row[column].value }}</a>
                    <span v-if="scope.row[column] && !scope.row[column].url"><span v-html="scope.row[column].value"></span></span>
                </template>
            </el-table-column>
            <el-table-column fixed="right" min-width="110" width="110" align="center">
                <template slot-scope="scope">
                    <a v-if="scope.row.downloaduri" :href="scope.row.downloaduri" target="_blank">
                        <el-button type="success" icon="el-icon-download" size="mini" circle></el-button>
                    </a>
                    <a v-if="scope.row.edituri" :href="scope.row.edituri">
                        <el-button type="primary" icon="el-icon-edit" size="mini" circle></el-button>
                    </a>
                    <el-button v-if="scope.row.deleteuri" type="danger" size="mini" @click="deleteEntity(scope.row.deleteuri)"
                               icon="el-icon-delete" circle></el-button>
                </template>
            </el-table-column>
        </el-table>
    </div>
</template>
<script>
    import axios from 'axios'
    import RowFunctions from '@/components/mgmt/Rowfunctions'

    export default {
        name: 'List',
        components: {RowFunctions},
        beforeRouteUpdate(to, from, next) {
            next();
            this.getCourses(false);
            var self = this;
            self.entity = this.$route.params.entity;
            self.urlcsv = process.env.API_URL + 'csv/' + self.entity;
            this.getCourses(false);
        },
        data: function () {
            return {
                urlcsv: '',
                entity: '',
                table: {columns: {}, columndata: {}},
                data: {},
                search: '',
                database: '',
                rf:{}
            }
        },
        mounted() {
            this.rf = new RowFunctions();
            this.getCourses(false);
        },
        created() {
            this.getCourses(false);
            var self = this;

            self.entity = this.$route.params.entity;
            self.urlcsv = process.env.API_URL + 'csv/' + self.entity + '?database=' + self.database;
        },
        methods: {
            searchnow: function () {
                this.getCourses(this.search);
            },
            deleteEntity: function (url) {
                this.rf.deleteEntity(this,axios,process.env.API_URL + url);
            },
            getCourses: function (q) {
                var self = this;

                if (typeof(this.$route.params.entity) !== 'undefined') {
                    var extension = '/' + this.$route.params.entity;
                }
                else {
                    var extension = this.$route.path;
                }

                if (typeof(this.$route.params.entity2) !== 'undefined') {
                    var postData = {
                        "q": q,
                        "isAddTo": this.selection,
                        "baseentity": this.$route.params.entity2,
                        "entityToAdd": this.$route.params.entity,
                        "entitykey": this.$route.params.entitykey,
                        "addToValue": this.$route.params.id
                    };
                } else {
                    var postData = {"q": q, "token": document.cookie};
                }
                const url = process.env.API_URL + 'mgmt/get' + extension;
                axios.post(url, {
                    data: postData,
                    dataType: 'json',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                }, {
                    headers: {
                        'Content-Type': 'text/plain;',
                    }
                }).then(function (response) {

                    if (response.data.result == 'expired') {
                        window.location.href = 'http://##';
                    }

                    self.table = response.data;
                    self.database = response.data.database;

                    self.urlcsv = process.env.API_URL + 'csv/' + self.entity + '?database=' + self.database;

                    if (document.body.clientWidth < 1465) {
                        var i = 0;
                        var columns = [];
                        for (const value of response.data.columns) {
                            if (i < 6) {
                                columns.push(value);
                            }
                            i++;
                        }
                        self.table['columns'] = columns;
                    }
                    self.data = response.data['data'];
                }).catch(function (error) {
                    console.log(error)
                })
            },
        }
    }
</script>
<style>

    .input-with-select .el-input-group__append {
        background-color: #fff;
        border: none;
        border-right: 1px solid #dcdfe6;
        border-radius: 0px;
    }

    .el-table .cell, .el-table th div {
        text-overflow: unset;
    }

    .el-table__column-filter-trigger {
        display: none;
    }

    .searchfield {
        position: fixed;
        height: 60px;
        left: 25vw;
        width: 50vw;
        top: 0px;
        z-index: 400;
    }

    .el-input__inner {
        border-radius: 0px !important;
    }

    .searchfield input {
        height: 60px;
        border-bottom: 0px;
        border-top: 0px;
    }

    table {
        position: relative;
        overflow: hidden;
        box-sizing: border-box;
        flex: 1;
        width: 100%;
        max-width: 100%;
        background-color: #fff;
        font-size: 14px;
        color: #606266;
        text-align: left;
        z-index: 0;
    }

    thead {
        color: #909399;
        font-weight: 500;
    }

    td, th {
        border-bottom: 1px solid #ebeef5;
        padding: 12px 0;
    }

    .vue-title {

        margin-bottom: 10px;
    }

    .glyphicon.glyphicon-eye-open {
        width: 16px;
        display: block;
        margin: 0 auto;
    }

    .pagination li {
        display: inline-block;
        margin: 0 3px;
    }

    .el-form {
        max-width: 500px;
    }

    .el-form-item {
        margin-bottom: 0;
    }

    .el-date-editor.el-input,
    .el-date-editor.el-input__inner,
    .el-select {
        max-width: 300px;
        width: 100%;
    }
</style>
